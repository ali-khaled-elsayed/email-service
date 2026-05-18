<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequestLog extends Model
{
    protected $fillable = [
        'application_id',
        'method',
        'path',
        'status_code',
        'ip_address',
        'request_body',
        'response_body',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_body' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
