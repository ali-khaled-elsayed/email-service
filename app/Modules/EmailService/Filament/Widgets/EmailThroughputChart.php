<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Widgets;

use App\Modules\EmailService\Models\EmailLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class EmailThroughputChart extends ChartWidget
{
    protected ?string $heading = 'Email Throughput (7 days)';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('M d');
            $data[] = EmailLog::query()
                ->whereDate('created_at', $date)
                ->where('status', 'sent')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Emails Sent',
                    'data' => $data,
                    'borderColor' => '#f59e0b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
