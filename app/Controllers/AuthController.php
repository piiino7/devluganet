<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Resources\UserResource;
use App\Services\JwtService;
use App\Services\RefreshTokenService;
use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;
use Illuminate\Database\Capsule\Manager as DB;

class AuthController extends BaseController
{
    private $logfile = 'auth.log';

    public function __construct(private JwtService $jwt, private array $client, private RefreshTokenService $refreshToken) {}

    public function welcome(): void
    {
        $this->json([
            'data' => [
                'message' => 'API is working'
            ],
        ]);
    }

    public function login(): void
    {
        try {
            $data = (new Validator($this->body()))
                ->rules([
                    'name'    => 'required|string',
                    'password' => 'required|string',
                ])
                ->validate();
            $user = User::where('name', $data['name'])->first();

            if (!$user || !$user->verifyPassword($data['password'])) {
                import_log('method AuthController->login() returns', [
                    'error' => 'Invalid credentials',
                    'login' => $data['name'],
                    'client_info' => $this->client,
                ], $this->logfile);

                throw HttpException::unauthorized('Invalid credentials');
            }

            if (!$user->is_active) {
                import_log('method AuthController->login() returns', [
                    'error' => 'Account is blocked',
                    'login' => $data['name'],
                    'client_info' => $this->client,
                ], $this->logfile);
                throw HttpException::forbidden('Account is blocked');
            }

            $role = $user->role;

            $accessToken = $this->jwt->issue($user->id, [
                'name' => $user->name,
                'role' => $role,
            ]);
            $refreshToken = $this->refreshToken->issue($user);

            import_log('method AuthController->login() returns', [
                'message' => 'success',
                'user' => $user->id,
                'role' => $role,
                'client_info' => $this->client,
            ], $this->logfile);
            $this->json([
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => date('Y-m-d H:i:s', time() + 900),
                    'user'       => (new UserResource($user))->toArray(),
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function refresh(): void
    {
        $data = (new Validator($this->body()))
            ->rules([
                'refresh_token' => 'required|string',
            ])
            ->validate();

        $oldToken = $this->refreshToken->verify($data['refresh_token']);

        $user = $oldToken->user;
        if (!$user || !$user->is_active) {
            import_log('method AuthController->login() refresh', [
                'error' => 'Account is blocked',
                'user' => $user->id,
                'client_info' => $this->client,
            ], $this->logfile);

            throw HttpException::forbidden('Account is blocked');
        }

        $role = $user->role;

        $accessToken  = $this->jwt->issue($user->id, [
            'name' => $user->name,
            'role'  => $role,
        ]);
        $refreshToken = $this->refreshToken->rotate($oldToken);

        import_log('method AuthController->login() refresh', [
            'message' => 'success',
            'user' => $user->id,
            'role' => $role,
            'client_info' => $this->client
        ], $this->logfile);

        $this->json([
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => date('Y-m-d H:i:s', time() + 900),
            ],
        ]);
    }

    public function logout(): void
    {
        $data = (new Validator($this->body()))
            ->rules([
                'refresh_token' => 'required|string',
            ])
            ->validate();

        $refreshToken = $this->refreshToken->verify($data['refresh_token']);

        $user = $refreshToken->user;
        if (!$user || !$user->is_active) {
            import_log('method AuthController->logout() refresh', [
                'error' => 'Account is blocked',
                'user' => $user->id,
                'client_info' => $this->client,
            ], $this->logfile);

            throw HttpException::forbidden('Account is blocked');
        }

        $role = $user->role;

        $this->refreshToken->revoke($data['refresh_token']);
        import_log('method AuthController->logout() refresh', [
            'message' => 'success',
            'user' => $user->id,
            'role' => $role,
            'client_info' => $this->client
        ], $this->logfile);

        $this->json([
            'data' => "Logout successfully",
        ]);
    }

    public function logoutAll(): void
    {
        $user = AuthUser::requireUser();
        $role = $user->role;

        $this->refreshToken->revokeAllForUser($user);
        import_log('method AuthController->logoutAll() refresh', [
            'message' => 'success',
            'user' => $user->id,
            'role' => $role,
            'client_info' => $this->client
        ], $this->logfile);

        $this->json([
            'data' => "Logout all successfully",
        ]);
    }

    public function changePassword(): void
    {
        $user = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'old_password' => 'required|string',
                'new_password' => 'required|string',
            ])
            ->validate();

        if (!$user->verifyPassword($data['old_password']) || $user->verifyPassword($data['new_password'])) {
            import_log('method AuthController->changePassword() refresh', [
                'error' => 'Invalid credentials',
                'login' => $data['name'],
                'client_info' => $this->client,
            ], $this->logfile);

            throw HttpException::badRequest('Invalid credentials');
        }

        if (!$user->is_active) {
            import_log('method AuthController->changePassword() refresh', [
                'error' => 'Account is blocked',
                'user' => $user->id,
                'client_info' => $this->client,
            ], $this->logfile);
            throw HttpException::forbidden('Account is blocked');
        }

        $changingPassword = $user->update([
            'password' => $data['new_password'],
        ]);

        if (!$changingPassword OR password_verify($data['old_password'], $user->password)) {
            import_log('method AuthController->changePassword() refresh', [
                'error' => 'Cannot update this password',
                'user' => $user->id,
                'client_info' => $this->client,
            ], $this->logfile);

            throw HttpException::forbidden('Cannot update this password');
        }

        $this->refreshToken->revokeAllExceptThis($user);

        import_log('method AuthController->changePassword() refresh', [
            'message' => 'success',
            'user' => $user->id,
            'client_info' => $this->client,
        ], $this->logfile);

        $this->json([
            'data' => [
                'status' => 'success',
                'message' => 'password was successfully changed',
                'user'       => (new UserResource($user))->toArray(),
            ],
        ]);
    }

    /*public function register(): void
    {
        try {
            $data = (new Validator($this->body()))
                ->rules([
                    'name' => 'required|max:255|min:5|string',
                    'password' => 'required|string|min:5',
                    'role' => 'required|string|in:admin,seller'
                ])
                ->validate();

            if (User::where('name', $data['name'])->exists()) {
                import_log('register failed', [
                    'error' => 'Name already taken',
                    'name' => $data['name'],
                    'client_info' => $this->client,
                ], $this->logfile);

                throw HttpException::validation(['name' => ['This name already taken']]);
            }

            $role = Role::where('name', $data['role'])->first();
            if (!$role) {
                import_log('register failed', [
                    'error' => 'Unknown role',
                    'role' => $data['role'],
                    'client_info' => $this->client,
                ], $this->logfile);

                throw HttpException::validation(['role' => ['Unknown role: ' . $data['role']]]);
            }

            try {
                $new_user = DB::connection()->transaction(function () use ($data, $role) {
                    $new_user = User::create([
                        'name' => $data['name'],
                        'password' => $data['password']
                    ]);

                    $new_user->roles()->sync([$role->id]);
                    $new_user->setRelation('roles', collect([$role]));

                    return $new_user;
                });
            } catch (\Throwable $e) {
                import_log('register failed', [
                    'error' => $e->getMessage(),
                    'status' => $e->status,
                    'client_info' => $this->client,
                ], $this->logfile);

                throw $e;
            }

            $deviceId   = $_SERVER['HTTP_X_DEVICE_ID'];
            $deviceName = $_SERVER['HTTP_X_DEVICE_NAME'];
            $device = [
                'device_id' => $deviceId,
                'device_name' => $deviceName
            ];

            $accessToken = $this->jwt->issue($new_user->id, [
                'name' => $new_user->name,
                'role' => $role,
            ]);
            $refreshToken = $this->refreshToken->issue($new_user, $device);

            import_log('register success', [
                'user' => $new_user->id,
                'role' => $role,
                'client_info' => $this->client,
            ], $this->logfile);
            $this->json([
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => date('Y-m-d H:i:s', time() + 900),
                    'user'       => (new UserResource(register))->toArray(),
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }*/

    public function me(): void
    {
        $user = AuthUser::requireUser();

        $this->json(['data' => (new UserResource($user))->toArray()]);
    }
}
