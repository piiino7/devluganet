<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Resources\UserResource;
use App\Services\JwtService;
use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

class AuthController extends BaseController
{
    public function __construct(private JwtService $jwt) {}

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
                throw HttpException::unauthorized('Invalid credentials');
            }

            $user->load('roles');
            $roles = $user->roles->pluck('name')->all();

            $token = $this->jwt->issue($user->id, [
                'email' => $user->email,
                'roles' => $roles,
            ]);

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

            $new_user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password']
            ]);

            $role = Role::where('name', $data['role'])->first();
            if (!$role) {
                throw HttpException::validation(['role' => ['Unknown role: ' . $data['role']]]);
            }
            $new_user->roles()->attach($role->id);
            $new_user->setRelation('roles', collect([$role]));
            $roles = $new_user->roles->pluck('name')->all();

            $token = $this->jwt->issue($new_user->id, [
                'email' => $new_user->email,
                'roles' => $roles,
            ]);

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
