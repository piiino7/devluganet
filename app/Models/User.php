<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Model
{
    protected $table      = 'users';
    protected $fillable   = ['name', 'email', 'password'];
    protected $hidden     = ['password'];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => password_get_info($value)['algo'] !== null
                ? $value
                : password_hash($value, PASSWORD_DEFAULT),
        );
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->password);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'users_role',
            'user_id',
            'role_id')
            ->withTimestamps();
    }
}