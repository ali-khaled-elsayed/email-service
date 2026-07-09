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
        private readonly EmailSettingsService $emailSettings,
    ) {}

    public function shouldRetry(EmailLog $emailLog, bool $retryable): bool
    {
        if (! $retryable) {
            return false;
        }

        return $emailLog->retry_count < $this->emailSettings->getMaxAttempts();
    }

    public function getDelay(int $attemptNumber): int
    {
        return $this->emailSettings->getDelay($attemptNumber);
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

            \App\Services\SystemLogger::error('Email delivery failed permanently', [
                'email_log_id' => $emailLog->id,
                'application_id' => $emailLog->application_id,
                'provider_id' => $providerId,
                'attempt_number' => $attemptNumber,
                'exception' => $exception,
            ]);

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

    /**
     * Get the current retry count for a given email log.
     */
    public function getRetryCount(EmailLog $emailLog): int
    {
        return $emailLog->retry_count;
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
