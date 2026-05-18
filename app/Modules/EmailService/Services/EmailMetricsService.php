<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\EmailMetric;

class EmailMetricsService
{
    public function increment(string $metric, ?EmailLog $emailLog = null, int $amount = 1): void
    {
        $record = EmailMetric::query()->firstOrCreate(
            [
                'date' => now()->toDateString(),
                'application_id' => $emailLog?->application_id,
                'provider_id' => $emailLog?->provider_id,
                'metric' => $metric,
            ],
            ['value' => 0],
        );

        $record->increment('value', $amount);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(?int $applicationId = null): array
    {
        $today = now()->toDateString();

        $query = EmailMetric::query()->where('date', $today);
        if ($applicationId) {
            $query->where('application_id', $applicationId);
        }

        $metrics = $query->get()->groupBy('metric')->map->sum('value');

        return [
            'sent_today' => (int) ($metrics['sent'] ?? 0),
            'failed_today' => (int) ($metrics['failed'] ?? 0),
            'queued' => EmailLog::query()->whereIn('status', ['pending', 'queued', 'processing'])->count(),
            'retry_count' => EmailLog::query()->where('status', 'retrying')->count(),
            'delivery_rate' => $this->calculateDeliveryRate($metrics),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<string, int>  $metrics
     */
    private function calculateDeliveryRate($metrics): float
    {
        $sent = (int) ($metrics['sent'] ?? 0);
        $failed = (int) ($metrics['failed'] ?? 0);
        $total = $sent + $failed;

        return $total > 0 ? round(($sent / $total) * 100, 2) : 100.0;
    }
}
