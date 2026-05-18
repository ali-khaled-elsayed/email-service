<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderHealthLog extends Model
{
    protected $fillable = [
        'provider_id',
        'status',
        'latency_ms',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
