<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $table      = 'users';
    protected $fillable   = ['name', 'email', 'password'];
    protected $hidden     = ['password'];

    protected function is_active(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (bool)$value,
        );
    }

    protected function deleted_at(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }

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

    public function role(): ?Role
    {
        return $this->roles->first();
    }

    public function hasRole(string $name): bool
    {
        return $this->role()?->name === $name;
    }

    public function isAdmin(): bool
    {
        return $this->role()?->name === 'admin';
    }
}