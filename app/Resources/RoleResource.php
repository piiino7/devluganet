<?php

namespace App\Resources;

use App\Models\Role;

class RoleResource extends Resource
{
    public function __construct(private Role $role) {}

    public function toArray(): array
    {
        return [
            'id'   => $this->role->id,
            'name' => $this->role->name,
            'description' => $this->role->description
        ];
    }
}
