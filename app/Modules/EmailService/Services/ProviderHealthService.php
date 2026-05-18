<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Enums\HealthStatus;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Models\ProviderHealthLog;
use App\Modules\EmailService\Providers\ProviderFactory;

class ProviderHealthService
{
    public function __construct(
        private readonly ProviderFactory $providerFactory,
    ) {}

    public function check(Provider $provider): HealthStatus
    {
        $adapter = $this->providerFactory->make($provider->type);
        $result = $adapter->healthCheck($provider);

        $status = $result->success ? HealthStatus::Healthy : HealthStatus::Unhealthy;

        ProviderHealthLog::query()->create([
            'provider_id' => $provider->id,
            'status' => $status->value,
            'latency_ms' => $result->response['latency_ms'] ?? null,
            'message' => $result->error,
            'metadata' => $result->response,
        ]);

        $provider->update([
            'health_status' => $status,
            'last_health_check_at' => now(),
        ]);

        return $status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllHealth(): array
    {
        return Provider::query()->get()->map(fn (Provider $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'health_status' => $p->health_status->value,
            'available' => $p->isAvailable(),
            'quota_used' => $p->quota_used,
            'quota_limit' => $p->quota_limit,
            'last_check' => $p->last_health_check_at?->toIso8601String(),
        ])->all();
    }
}
