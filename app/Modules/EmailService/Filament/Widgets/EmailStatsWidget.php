<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Widgets;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Services\EmailMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class EmailStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = app(EmailMetricsService::class)->getDashboardMetrics();

        return [
            Stat::make('Sent Today', (string) $metrics['sent_today'])
                ->description('Successfully sent')
                ->color('success'),
            Stat::make('Failed Today', (string) $metrics['failed_today'])
                ->color('danger'),
            Stat::make('Queue Size', (string) DB::table('jobs')->count())
                ->description('Pending jobs'),
            Stat::make('Retrying', (string) $metrics['retry_count']),
            Stat::make('Delivery Rate', $metrics['delivery_rate'].'%'),
            Stat::make('Healthy Providers', (string) Provider::query()->where('health_status', 'healthy')->count()),
        ];
    }
}
