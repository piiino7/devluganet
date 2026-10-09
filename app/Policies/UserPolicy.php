<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public static function manage(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return false;
        }

        if ($target->isSuperAdmin()) {
            return false;
        }

        if ($target->isAdmin() AND !$actor->isSuperAdmin()) {
            return false;
        }
        
        return $actor->isAdmin() || $actor->isSuperAdmin();
    }

    public static function block(User $actor, User $target): bool
    {
        return self::manage($actor, $target);
    }

    public static function restore(User $actor, User $target): bool
    {
        return self::manage($actor, $target);
    }

    public static function remove(User $actor, User $target): bool
    {
        return self::manage($actor, $target);
    }

    public static function update(User $actor, User $target): bool
    {
        return self::manage($actor, $target);
    }
}