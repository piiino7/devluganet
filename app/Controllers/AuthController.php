<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Resources\UserResource;
use App\Services\JwtService;
use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;
use Illuminate\Database\Capsule\Manager as DB;

class AuthController extends BaseController
{
    private $logfile = 'auth.log';

    public function __construct(private JwtService $jwt, private array $client) {}

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
                    'email'    => 'required|email',
                    'password' => 'required|string',
                ])
                ->validate();
            $user = User::where('email', $data['email'])->first();

            if (!$user || !$user->verifyPassword($data['password'])) {
                import_log('login failed', [
                    'error' => 'Invalid credentials',
                    'email' => $data['email'],
                    'login' => $data['login'],
                    'client_info' => $this->client,
                ], $this->logfile);

                throw HttpException::unauthorized('Invalid credentials');
            }

            $role = $user->role()?->name;

            $token = $this->jwt->issue($user->id, [
                'email' => $user->email,
                'role' => $role,
            ]);

            import_log('login success', [
                'user' => $user->id,
                'role' => $role,
                'client_info' => $this->client,
            ], $this->logfile);
            $this->json([
                'data' => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'user'       => (new UserResource($user))->toArray(),
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function register(): void
    {
        try {
            $data = (new Validator($this->body()))
                ->rules([
                    'email'    => 'required|email',
                    'name' => 'required|max:255|min:2|string',
                    'password' => 'required|string|min:5',
                    'role' => 'required|string|in:admin,seller'
                ])
                ->validate();

            if (User::where('email', $data['email'])->exists()) {
                import_log('register failed', [
                    'error' => 'Email already taken',
                    'email' => $data['email'],
                    'client_info' => $this->client,
                ], $this->logfile);

                throw HttpException::validation(['email' => ['Email already taken']]);
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
                        'email' => $data['email'],
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

            $token = $this->jwt->issue($new_user->id, [
                'email' => $new_user->email,
                'role' => $role->name,
            ]);

            import_log('register success', [
                'user' => $new_user->id,
                'client_info' => $this->client,
            ], $this->logfile);
            $this->json([
                'data' => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'user'       => (new UserResource($new_user))->toArray(),
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function me(): void
    {
        $user = AuthUser::requireUser();
        $user->load('roles');

        $this->json(['data' => (new UserResource($user))->toArray()]);
    }
}
