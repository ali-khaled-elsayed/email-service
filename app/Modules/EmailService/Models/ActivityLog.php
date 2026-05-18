<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
        'causer_type',
        'causer_id',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }
}
