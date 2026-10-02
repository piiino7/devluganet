<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ImportLog extends Model
{
    protected $table = 'import_logs';
    protected $guarded = [];

    protected $casts = [
        'started_at'    => 'datetime',
        'finished_at'   => 'datetime'
    ];

    /*protected function startedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }

    protected function finishedAt(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? new \DateTime($value) : null,
        );
    }*/
}