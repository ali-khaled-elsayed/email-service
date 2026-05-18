<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Jobs\SendEmailJob;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\FailedEmailAttempt;
use App\Modules\EmailService\Repositories\EmailLogRepository;

class RetryManagerService
{
    public function __construct(
        private readonly EmailLogRepository $emailLogRepository,
    ) {}

    public function shouldRetry(EmailLog $emailLog, bool $retryable): bool
    {
        if (! $retryable) {
            return false;
        }

        return $emailLog->retry_count < config('email_service.max_attempts');
    }

    public function getDelay(int $attemptNumber): int
    {
        $delays = config('email_service.retry_delays');

        return (int) ($delays[$attemptNumber] ?? end($delays));
    }

    public function scheduleRetry(EmailLog $emailLog, string $exception, bool $retryable, int $providerId): void
    {
        $attemptNumber = $emailLog->retry_count + 1;

        FailedEmailAttempt::query()->create([
            'email_log_id' => $emailLog->id,
            'provider_id' => $providerId,
            'exception' => $exception,
            'retryable' => $retryable,
            'attempt_number' => $attemptNumber,
        ]);

        if (! $this->shouldRetry($emailLog, $retryable)) {
            $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Failed, $exception);
            $emailLog->update(['failed_at' => now(), 'error_message' => $exception]);

            return;
        }

        $emailLog->increment('retry_count');
        $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Retrying, "Retry attempt {$attemptNumber}");

        $delay = $this->getDelay($attemptNumber);
        $queue = config('email_service.queues.retry');

        SendEmailJob::dispatch($emailLog->id)
            ->onQueue($queue)
            ->delay(now()->addSeconds($delay));
    }

    public function manualRetry(EmailLog $emailLog): void
    {
        if (! $emailLog->status->canRetry()) {
            throw new \RuntimeException('Email cannot be retried in current status.');
        }

        $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Retrying, 'Manual retry');
        SendEmailJob::dispatch($emailLog->id)->onQueue(config('email_service.queues.retry'));
    }
}
