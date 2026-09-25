<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Resources\UserResource;
use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;
use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class AdminController extends BaseController
{
    public function __construct() {}

    public function register_employer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'email'    => 'required|email',
                'name' => 'required|max:255|min:2|string',
                'password' => 'required|string|min:5',
                'role' => 'required|string|in:seller,operator'
            ])
            ->validate();

        if (User::where('email', $data['email'])->exists()) {
            throw HttpException::validation(['email' => ['Email already taken']]);
        }

        $role = Role::where('name', $data['role'])->first();
        if (!$role) {
            throw HttpException::validation(['role' => ['Unknown role: ' . $data['role']]]);
        }

        try {
            $new_user = DB::connection()->transaction(function () use ($data, $role) {
                $new_user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => $data['password'],
                ]);

                $new_user->roles()->sync([$role->id]);
                $new_user->setRelation('roles', collect([$role]));

                return $new_user;
            });
        } catch (\Exception $e) {
            throw HttpException::validation(['email' => ['Email already taken']]);
        }

        $this->json([
            'data' => [
                'user' => (new UserResource($new_user))->toArray(),
                'created_by' => (new UserResource($admin))->toArray()
            ],
        ]);
    }

    public function block_employer(): void
    {
        $admin = AuthUser::requireUser();
        $admin->load('roles');

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $users = User::with('roles')->find($ids);

        if (count($users) !== count($ids)) {
            $found   = $users->pluck('id')->all();
            $missing = array_diff($ids, $found);
            throw HttpException::validation([
                'id' => ['Users not found: ' . implode(', ', $missing)],
            ]);
        }

        $blocked = [];
        $skipped = [];

        foreach ($users as $user) {
            if (!UserPolicy::block($admin, $user)) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Cannot block this user'];
                continue;
            }

            if (!$user->is_active) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Already blocked'];
                continue;
            }
            $user->update(['is_active' => false]);
            $blocked[] = $user;
        }

        $this->json([
            'data' => [
                'blocked_users' => UserResource::collection($blocked),
                'skipped'       => $skipped,
                'blocked_by'    => (new UserResource($admin))->toArray(),
            ],
        ]);
    }

    public function restore_employer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $restored_users = User::with('roles')->find($ids);

        if (count($restored_users) !== count($ids)) {
            $found   = $restored_users->pluck('id')->all();
            $missing = array_diff($ids, $found);
            throw HttpException::validation([
                'id' => ['Users not found: ' . implode(', ', $missing)],
            ]);
        }

        $restored = [];
        $skipped = [];

        foreach ($restored_users as $user) {
            if (!UserPolicy::restore($admin, $user)) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Cannot restore this user'];
                continue;
            }

            if ($user->is_active) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Already active'];
                continue;
            }

            $user->update(['is_active' => true]);
            $restored[] = $user;
        }

        $this->json([
            'data' => [
                '$restored' => UserResource::collection($restored),
                'skipped' => $skipped,
                'restored_by' => (new UserResource($admin))->toArray()
            ],
        ]);
    }

    public function remove_employer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $deleted_users = User::with('roles')->find($ids);

        if (count($deleted_users) !== count($ids)) {
            $found   = $deleted_users->pluck('id')->all();
            $missing = array_diff($ids, $found);
            throw HttpException::validation([
                'id' => ['Users not found: ' . implode(', ', $missing)],
            ]);
        }

        $deleted = [];
        $skipped = [];

        foreach ($deleted_users as $user) {
            if (!UserPolicy::remove($admin, $user)) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Cannot remove this user'];
                continue;
            }

            if ($user->deleted_at) {
                $skipped[] = ['id' => $user->id, 'reason' => 'Already deleted'];
                continue;
            }

            $user->roles()->detach();
            $user->update(['is_active' => false]);
            $user->delete(); // SoftDelete

            $deleted[] = $user->id;
        }

        $this->json([
            'data' => [
                'deleted_users' => $deleted,
                'skipped'       => $skipped,
                'deleted_by' => (new UserResource($admin))->toArray()
            ],
        ]);
    }

    public function list_of_employers(): void
    {
        try {
            $admin = AuthUser::requireUser();

            $workers = User::whereHas('roles', fn($q) => $q->whereIn('name', ['operator', 'seller']))->get();

            $this->json([
                'data' => [
                    'all_workers' => UserResource::collection($workers),
                    'asked_by' => (new UserResource($admin))->toArray()
                ],
            ]);
        } catch (\Throwable $error) {
            throw $error;
        }
    }

    public function get_employer(): void
    {
        try {
            $admin = AuthUser::requireUser();

            $data = (new Validator($this->body()))
                ->rules([
                    'id'    => 'required|int',
                ])
                ->validate();

            $finded_user = User::with('roles')->find($data['id']);

            $this->json([
                'data' => [
                    'worker' => (new UserResource($finded_user))->toArray(),
                    'asked_by' => (new UserResource($admin))->toArray()
                ],
            ]);
        } catch (\Throwable $e) {
            throw HttpException::validation([
                'id' => ['User not found: ' . $data['id']],
            ]);
        }
    }

    public function update_employer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|int',
                'name' => 'max:255|min:2|string',
                'password' => 'string|min:5',
                'role' => 'string|in:seller,operator',
                'is_active' => 'int|in:0,1'
            ])
            ->validate();

        $updated_user = User::with('roles')->find($data['id']);

        if (!$updated_user) {
            throw HttpException::notFound('User not found');
        }

        if (!UserPolicy::update($admin, $updated_user)) {
            throw HttpException::forbidden('Cannot update this user');
        }

        try {
            DB::connection()->transaction(function () use ($updated_user, $data) {
                $new_role = $data['role'] ?? null;
                unset($data['role']);

                if ($data !== []) {
                    $updated_user->update($data);
                }

                if ($new_role !== null) {
                    $role = Role::where('name', $new_role)->first();
                    if (!$role) {
                        throw HttpException::validation(['role' => ['Unknown role: ' . $new_role]]);
                    }
                    $updated_user->roles()->sync([$role->id]);
                }

                $updated_user->refresh();
            });
        } catch (\Throwable $e) {
            throw HttpException::forbidden('Cannot update this user');
        }

        $this->json([
            'data' => [
                'updated_employer' => (new UserResource($updated_user))->toArray(),
                'asked_by' => (new UserResource($admin))->toArray()
            ],
        ]);
    }

    public function report(): void
    {
        //TODO СДЕЛАТЬ ОТЧЁТ С ФИЛЬТРАМИ ПО ДАТЕ, СОТРУДНИКУ, НОМЕНКЛАТУРЕ
    }

    public function me(): void
    {
        $user = AuthUser::requireUser();
        $user->load('roles');

        $this->json(['data' => (new UserResource($user))->toArray()]);
    }
}
