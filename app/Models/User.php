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
    protected $fillable   = ['name', 'password', 'role', 'is_active'];
    protected $hidden     = ['password'];

    protected $casts = [
      'is_active' => 'boolean',
      'deleted_at' => 'datetime'
    ];

    /*protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (bool)$value,
        );
    }

    protected function deletedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }*/

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

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class, 'seller_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }
}