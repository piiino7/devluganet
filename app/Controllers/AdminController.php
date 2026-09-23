<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Resources\UserResource;
use App\Services\JwtService;
use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

class AdminController extends BaseController
{
    public function __construct(private JwtService $jwt) {}

    public function register(): void
    {
        try {
            $admin = AuthUser::requireUser();

            $data = (new Validator($this->body()))
                ->rules([
                    'email'    => 'required|email',
                    'name' => 'required|max:255|min:2|string',
                    'password' => 'required|string|min:5',
                    'role' => 'required|string|in:admin,operator'
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

            $this->json([
                'data' => [
                    'user' => (new UserResource($new_user))->toArray(),
                    'created_by' => (new UserResource($admin))->toArray()
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function remove_employer(): void
    {
        try {
            $admin = AuthUser::requireUser();

            $data = (new Validator($this->body()))
                ->rules([
                    'id'    => 'required|array',
                ])
                ->validate();

            $new_user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password']
            ]);

            $deleted_users = User::findOrFail($data['id']);
            foreach ($deleted_users as $user) {
                $user->role()->detach();
                $user->delete(); // Настроить SoftDelete
            }

            $this->json([
                'data' => [
                    'deleted_users' => $data['id'],
                    'deleted_by' => (new UserResource($admin))->toArray()
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function list_of_employers(): void
    {
        try {
            $admin = AuthUser::requireUser();

            $data = (new Validator($this->body()))
                ->rules([
                    'id'    => 'required|array',
                ])
                ->validate();

            // ВЫВОД ВСЕХ ОПЕРАТОРОВ И ПРОДАВЦОВ

            $this->json([
                'data' => [
                    'deleted_users' => $data['id'],
                    'deleted_by' => (new UserResource($admin))->toArray()
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
