<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Pages;

use App\Enums\Permission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueueMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?string $navigationLabel = 'Queue Monitor';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'queue-monitor';

    protected string $view = 'filament.pages.queue-monitor';

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::ViewQueue->value) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Queue Monitor';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn (): null => null),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function queueStats(): array
    {
        $now = time();

        return DB::table('jobs')
            ->get()
            ->groupBy('queue')
            ->map(function ($jobs, string $queue) use ($now): array {
                return [
                    'queue' => $queue,
                    'total' => $jobs->count(),
                    'ready' => $jobs->whereNull('reserved_at')->where('available_at', '<=', $now)->count(),
                    'delayed' => $jobs->whereNull('reserved_at')->where('available_at', '>', $now)->count(),
                    'reserved' => $jobs->whereNotNull('reserved_at')->count(),
                ];
            })
            ->sortBy('queue')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingJobs(): array
    {
        return DB::table('jobs')
            ->orderByDesc('id')
            ->get()
            ->map(fn (object $job): array => [
                'id' => $job->id,
                'queue' => $job->queue,
                'name' => $this->payloadValue($job->payload, 'displayName')
                    ?? $this->payloadValue($job->payload, 'job')
                    ?? 'Queued job',
                'email_log_id' => $this->emailLogIdFromPayload($job->payload),
                'attempts' => $job->attempts,
                'state' => $this->jobState($job),
                'available_at' => $this->formatTimestamp((int) $job->available_at),
                'reserved_at' => $this->formatTimestamp($job->reserved_at ? (int) $job->reserved_at : null),
                'created_at' => $this->formatTimestamp((int) $job->created_at),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function failedJobs(): array
    {
        return DB::table('failed_jobs')
            ->orderByDesc('id')
            ->get()
            ->map(fn (object $job): array => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'name' => $this->payloadValue($job->payload, 'displayName')
                    ?? $this->payloadValue($job->payload, 'job')
                    ?? 'Failed job',
                'email_log_id' => $this->emailLogIdFromPayload($job->payload),
                'failed_at' => Carbon::parse($job->failed_at)->toDateTimeString(),
                'exception' => $this->firstLine((string) $job->exception),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function batches(): array
    {
        return DB::table('job_batches')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (object $batch): array => [
                'id' => $batch->id,
                'name' => $batch->name,
                'total_jobs' => $batch->total_jobs,
                'pending_jobs' => $batch->pending_jobs,
                'failed_jobs' => $batch->failed_jobs,
                'progress' => $this->batchProgress((int) $batch->total_jobs, (int) $batch->pending_jobs),
                'status' => $this->batchStatus($batch),
                'created_at' => $this->formatTimestamp((int) $batch->created_at),
                'finished_at' => $this->formatTimestamp($batch->finished_at ? (int) $batch->finished_at : null),
            ])
            ->all();
    }

    public function totals(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'batches' => DB::table('job_batches')->count(),
            'queues' => count($this->queueStats()),
        ];
    }

    private function payloadValue(string $payload, string $key): ?string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        $value = $decoded[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }

    private function emailLogIdFromPayload(string $payload): ?int
    {
        if (preg_match('/emailLogId";i:(\d+)/', $payload, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/"emailLogId"\s*:\s*(\d+)/', $payload, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    private function jobState(object $job): string
    {
        if ($job->reserved_at !== null) {
            return 'Reserved';
        }

        return (int) $job->available_at > time() ? 'Delayed' : 'Ready';
    }

    private function batchProgress(int $totalJobs, int $pendingJobs): int
    {
        if ($totalJobs === 0) {
            return 100;
        }

        return (int) round((($totalJobs - $pendingJobs) / $totalJobs) * 100);
    }

    private function batchStatus(object $batch): string
    {
        if ($batch->cancelled_at !== null) {
            return 'Cancelled';
        }

        if ($batch->finished_at !== null) {
            return 'Finished';
        }

        return 'Running';
    }

    private function formatTimestamp(?int $timestamp): string
    {
        if ($timestamp === null) {
            return '-';
        }

        return Carbon::createFromTimestamp($timestamp)->toDateTimeString();
    }

    private function firstLine(string $text): string
    {
        return str($text)->before("\n")->limit(160)->toString();
    }
}
