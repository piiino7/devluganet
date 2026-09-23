<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ImportLog extends Model
{
    protected $table = 'import_logs';
    protected $guarded = [];

    protected function started_at(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }

    protected function finished_at(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }
}