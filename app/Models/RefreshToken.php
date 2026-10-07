<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $table      = 'refresh_tokens';
    protected $fillable   = ['user_id', 'device_id', 'device_name', 'token_hash', 'expires_at', 'revoked_at', 'replaced_by', 'user_agent', 'ip'];
    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}