<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderRateLimit extends Model
{
    protected $fillable = [
        'provider_id',
        'period',
        'limit',
        'used',
        'resets_at',
    ];

    protected function casts(): array
    {
        return [
            'resets_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
