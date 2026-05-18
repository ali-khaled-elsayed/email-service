<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use App\Modules\EmailService\Enums\HealthStatus;
use App\Modules\EmailService\Enums\ProviderStatus;
use App\Modules\EmailService\Enums\ProviderType;
use App\Modules\EmailService\Traits\HasEncryptedConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasEncryptedConfig;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'status',
        'priority',
        'config',
        'health_status',
        'quota_limit',
        'quota_used',
        'timeout',
        'weight',
        'last_health_check_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProviderType::class,
            'status' => ProviderStatus::class,
            'health_status' => HealthStatus::class,
            'last_health_check_at' => 'datetime',
        ];
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function healthLogs(): HasMany
    {
        return $this->hasMany(ProviderHealthLog::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === ProviderStatus::Active
            && $this->health_status->isAvailable()
            && ! $this->isQuotaExceeded();
    }

    public function isQuotaExceeded(): bool
    {
        return $this->quota_limit !== null && $this->quota_used >= $this->quota_limit;
    }

    protected static function newFactory(): \Database\Factories\ProviderFactory
    {
        return \Database\Factories\ProviderFactory::new();
    }
}
