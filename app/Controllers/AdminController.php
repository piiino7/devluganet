<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;

use App\Resources\UserResource;
use App\Resources\DetailProductResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class AdminController extends BaseController
{
    public function __construct() {}

    public function registerEmployer(): void
    {
        $admin = AuthUser::requireUser();
        $rules = [
            'name' => 'required|max:255|min:5|string',
            'password' => 'required|string|min:5',
            'role' => 'required|string|in:seller,operator'
        ];

        if ($admin->isSuperAdmin()) {
            $rules['role'] .= ',admin';
        }

        $data = (new Validator($this->body()))
            ->rules($rules)
            ->validate();

        if (User::where('name', $data['name'])->exists()) {
            import_log('method AdminController->registerEmployer() returns', [
                'error' => 'Name already taken',
                'name' => $data['name'],
                'registered_by' => $admin->id
            ]);

            throw HttpException::validation(['name' => ['This name already taken']]);
        }

        try {
            $new_user = DB::connection()->transaction(function () use ($data) {
                $new_user = User::create([
                    'name'     => $data['name'],
                    'password' => $data['password'],
                    'role'     => $data['role']
                ]);

                return $new_user;
            });
        } catch (\Throwable $e) {
            import_log('method AdminController->registerEmployer() returns', [
                'error' => $e->getMessage(),
                'status' => $e->status,
                'registered_by' => $admin->id,
            ]);

            throw $e;
        }

        import_log('method AdminController->registerEmployer() returns', [
            'message' => 'success',
            'user' => $new_user->id,
            'registered_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                'user' => (new UserResource($new_user))->toArray()
            ],
        ]);
    }

    public function blockEmployer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $users = User::find($ids);

        if (count($users) !== count($ids)) {
            $found   = $users->pluck('id')->all();
            $missing = array_diff($ids, $found);

            import_log('method AdminController->blockEmployer() returns', [
                'error' => 'Users not found',
                'missing_users' => $missing,
                'blocked_by' => $admin->id,
            ]);

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

        import_log('method AdminController->blockEmployer() returns', [
            'message' => 'success',
            'blocked_users' => collect($blocked)->pluck('id')->toArray(),
            'skipped_users' => $skipped,
            'blocked_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                'blocked_users' => UserResource::collection($blocked),
                'skipped'       => $skipped
            ],
        ]);
    }

    public function restoreEmployer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $restored_users = User::find($ids);

        if (count($restored_users) !== count($ids)) {
            $found   = $restored_users->pluck('id')->all();
            $missing = array_diff($ids, $found);

            import_log('method AdminController->restoreEmployer() returns', [
                'error' => 'Users not found',
                'missing_users' => $missing,
                'restored_by' => $admin->id,
            ]);
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

        import_log('method AdminController->restoreEmployer() return', [
            'message' => 'success',
            'restored_users' => collect($restored)->pluck('id')->toArray(),
            'skipped_users' => $skipped,
            'restored_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                '$restored' => UserResource::collection($restored),
                'skipped' => $skipped
            ],
        ]);
    }

    public function removeEmployer(): void
    {
        $admin = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'id'    => 'required|array',
                'id.*'  => 'int'
            ])
            ->validate();

        $ids   = $data['id'];
        $deleted_users = User::find($ids);

        if (count($deleted_users) !== count($ids)) {
            $found   = $deleted_users->pluck('id')->all();
            $missing = array_diff($ids, $found);

            import_log('method AdminController->removeEmployer() return', [
                'error' => 'Users not found',
                'missing_users' => $missing,
                'deleted_by' => $admin->id,
            ]);
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

            $user->update(['is_active' => false]);
            $user->delete(); // SoftDelete

            $deleted[] = $user->id;
        }

        import_log('method AdminController->removeEmployer() return', [
            'message' => 'success',
            'deleted_users' => $deleted,
            'skipped_users' => $skipped,
            'deleted_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                'deleted_users' => $deleted,
                'skipped'       => $skipped
            ],
        ]);
    }

    public function listOfEmployers(): void
    {
        $admin = AuthUser::requireUser();

        $roles = ['operator', 'seller'];

        if ($admin->isSuperAdmin()) {
            $roles[] = 'admin';
        }

        $workers = User::whereIn('role', $roles)->get();
        import_log('method AdminController->listOfEmployers() returns', [
            'message' => 'success',
            '$workers' => $workers->pluck('id')->toArray(),
            'asked_by' => $admin->id,
        ]);

        $this->json([
            'data' => [
                'all_workers' => UserResource::collection($workers)
            ],
        ]);
    }

    public function getEmployer(string $employerId): void
    {
        $admin = AuthUser::requireUser();

        $body = $this->body();
        $body['id'] = $employerId;

        $data = (new Validator($body))
            ->rules([
                'id'    => 'required|int',
            ])
            ->validate();

        $finded_user = User::find($data['id']);

        if ($finded_user === null) {
            import_log('method AdminController->getEmployer() returns', [
                'error' => 'employer not found',
                'asked_by' => $admin->id,
            ]);
            throw HttpException::notFound('Employer not found');
        }

        import_log('method AdminController->getEmployer() returns', [
            'message' => 'success',
            'employer' => $finded_user->id,
            'asked_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                'worker' => (new UserResource($finded_user))->toArray()
            ],
        ]);
    }

    public function updateEmployer(string $employerId): void
    {
        $admin = AuthUser::requireUser();

        $rules = [
            'id'    => 'required|int',
            'name' => 'max:255|min:2|string',
            'password' => 'string|min:5',
            'role' => 'string|in:seller,operator',
            'is_active' => 'int|in:0,1'
        ];
        if ($admin->isSuperAdmin()) {
            $rules['role'] .= ',admin';
        }

        $body = $this->body();
        $body['id'] = $employerId;

        $data = (new Validator($body))
            ->rules($rules)
            ->validate();

        $updated_user = User::find($data['id']);

        if (!$updated_user) {
            import_log('method AdminController->updateEmployer() returns', [
                'error' => 'User not found',
                'updated_user' => $data['id'],
                'updated_by' => $admin->id,
            ]);

            throw HttpException::notFound('User not found');
        }

        if (!UserPolicy::update($admin, $updated_user)) {
            import_log('method AdminController->updateEmployer() returns', [
                'error' => 'Forbidden',
                'updated_user' => $updated_user->id,
                'updated_by' => $admin->id,
            ]);

            throw HttpException::forbidden('Cannot update this user');
        }

        try {
            DB::connection()->transaction(function () use ($updated_user, $data) {
                if ($data !== []) {
                    $updated_user->update($data);
                }

                $updated_user->refresh();
            });
        } catch (\Throwable $e) {
            import_log('method AdminController->updateEmployer() returns', [
                'error' => $e->getMessage(),
                'status' => $e->status,
                'updated_by' => $admin->id,
            ]);

            throw $e;
        }

        import_log('method AdminController->updateEmployer() returns', [
            'message' => 'success',
            'updated_employer' => $updated_user->id,
            'updated_by' => $admin->id,
        ]);
        $this->json([
            'data' => [
                'updated_employer' => (new UserResource($updated_user))->toArray()
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

        $this->json(['data' => (new UserResource($user))->toArray()]);
    }
}
