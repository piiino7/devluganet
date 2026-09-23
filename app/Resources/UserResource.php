<?php

namespace App\Resources;

use App\Models\User;

class UserResource extends Resource
{
    public function __construct(private User $user) {}

    public function toArray(): array
    {
        return [
            'id'         => $this->user->id,
            'name'       => $this->user->name,
            'email'      => $this->user->email,
            'created_at' => $this->user->created_at,
            'roles'      => $this->user->relationLoaded('roles')
                ? RoleResource::collection($this->user->roles)
                : null,
        ];
    }
}
