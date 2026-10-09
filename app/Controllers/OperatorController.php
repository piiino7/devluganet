<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\Offer;

use App\Resources\UserResource;
use App\Resources\ShortProductResource;
use App\Resources\DetailProductResource;
use App\Resources\OfferResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;

class OperatorController extends BaseController {

    public function __construct() {}

    public function getSales(): void {}

    public function getSellers(): void
    {
        $operator = AuthUser::requireUser();

        $sellers = User::where('role', 'seller')->get();

        import_log('method OperatorController->getSellers() returns', [
            'sellers' => $sellers->pluck('id')->toArray(),
            'asked_by' => $operator->id,
        ]);

        $this->json([
            'data' => [
                'sellers' => UserResource::collection($sellers),
            ]
        ]);
    }

    public function getSeller(string $sellerId): void
    {
        $operator = AuthUser::requireUser();

        $data = (new Validator(['sellerId' => $sellerId]))
            ->rules([
                'sellerId'    => 'required|int',
            ])
            ->validate();

        $seller = User::find($data['sellerId']);

        if ($seller === null) {
            import_log('method OperatorController->getSeller() returns', [
                'error' => 'Seller not found',
                'asked_by' => $operator->id,
            ]);
            throw HttpException::notFound('Seller not found');
        }

        import_log('method OperatorController->getSeller() returns', [
            'message' => 'success',
            'seller' => $seller->id,
            'asked_by' => $operator->id,
        ]);
        $this->json([
            'data' => [
                'product' => (new UserResource($seller))->toArray()
            ],
        ]);
    }

    public function registerSeller(): void
    {
        $operator = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'name' => 'required|max:255|min:5|string',
                'password' => 'required|string|min:5'
            ])
            ->validate();

        if (User::where('name', $data['name'])->exists()) {
            import_log('method OperatorController->registerSeller() returns', [
                'error' => 'Name already taken',
                'name' => $data['name'],
                'registered_by' => $operator->id
            ]);

            throw HttpException::validation(['name' => ['This name already taken']]);
        }

        try {
            $new_seller = DB::connection()->transaction(function () use ($data) {
                $new_seller = User::create([
                    'name'     => $data['name'],
                    'password' => $data['password'],
                    'role'     => 'seller'
                ]);

                return $new_seller;
            });
        } catch (\Throwable $e) {
            import_log('method OperatorController->registerSeller() returns', [
                'error' => $e->getMessage(),
                'status' => $e->status,
                'registered_by' => $operator->id,
            ]);

            throw $e;
        }

        import_log('method OperatorController->registerSeller() returns', [
            'message' => 'success',
            'new_seller' => $new_seller->id,
            'registered_by' => $operator->id,
        ]);
        $this->json([
            'data' => [
                'new_seller' => (new UserResource($new_seller))->toArray()
            ],
        ]);
    }

    public function changeSellerPassword(string $sellerId): void
    {
        $operator = AuthUser::requireUser();
        $body = $this->body();
        $body['sellerId'] = $sellerId;

        $data = (new Validator($body))
            ->rules([
                'sellerId'    => 'required|int',
                'old_password' => 'required|string',
                'new_password' => 'required|string',
            ])
            ->validate();

        $seller = User::where('role', 'seller')->find($data['sellerId']);

        if ($seller === null) {
            import_log('method OperatorController->changeSellerPassword() returns', [
                'error' => 'Seller not found',
                'asked_by' => $operator->id,
            ]);
            throw HttpException::notFound('Seller not found');
        }

        if (!$seller->verifyPassword($data['old_password']) || $seller->verifyPassword($data['new_password'])) {
            import_log('method OperatorController->changeSellerPassword() returns', [
                'error' => 'Invalid credentials',
                'seller' => $seller->id,
                'changed_by' => $operator->id
            ]);

            throw HttpException::badRequest('Invalid credentials');
        }


        $changingPassword = $seller->update([
            'password' => $data['new_password'],
        ]);

        if (!$changingPassword OR password_verify($data['old_password'], $seller->password)) {
            import_log('method OperatorController->changeSellerPassword() returns', [
                'error' => 'Cannot update this password',
                'seller' => $seller->id,
                'changed_by' => $operator->id
            ]);

            throw HttpException::forbidden('Cannot update this password');
        }

        //$this->refreshToken->revokeAllForUser($seller);

        import_log('method OperatorController->changeSellerPassword() returns', [
            'message' => 'success',
            'seller' => $seller->id,
            'changed_by' => $operator->id
        ]);

        $this->json([
            'data' => [
                'status' => 'success',
                'message' => 'password was successfully changed',
                'seller'       => (new UserResource($seller))->toArray(),
            ],
        ]);
    }

    public function salesReport(): void {}
}