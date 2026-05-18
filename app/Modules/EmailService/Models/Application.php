<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use App\Modules\EmailService\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'app_key',
        'status',
        'default_provider_id',
        'fallback_provider_id',
        'rate_limit',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'settings' => 'array',
        ];
    }

    public function defaultProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'default_provider_id');
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'fallback_provider_id');
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    public function isActive(): bool
    {
        return $this->status === ApplicationStatus::Active;
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    protected static function newFactory(): \Database\Factories\ApplicationFactory
    {
        return \Database\Factories\ApplicationFactory::new();
    }
}
