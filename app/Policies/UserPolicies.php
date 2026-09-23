<?php

namespace App\Policies;

use App\Models\User;
use App\Support\AuthContext;

class UserPolicy
{
    public static function viewAny(): bool
    {
        return in_array('admin', AuthContext::roles(), true);
    }

    public static function view(User $target): bool
    {
        return in_array('admin', AuthContext::roles(), true)
            || AuthContext::id() === $target->id;
    }

    public static function create(): bool
    {
        return in_array('admin', AuthContext::roles(), true);
    }

    public static function update(User $target): bool
    {
        return in_array('admin', AuthContext::roles(), true)
            || AuthContext::id() === $target->id;
    }

    public static function delete(User $target): bool
    {
        return in_array('admin', AuthContext::roles(), true);
    }
}