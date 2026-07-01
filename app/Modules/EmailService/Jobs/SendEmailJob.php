<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Jobs;

use App\Modules\EmailService\Services\EmailDispatcherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $emailLogId,
    ) {}

    public function handle(EmailDispatcherService $dispatcher): void
    {
        $dispatcher->process($this->emailLogId);
    }

    public function failed(\Throwable $exception): void
    {
        \App\Services\SystemLogger::logException('SendEmailJob failed', $exception, [
            'email_log_id' => $this->emailLogId,
            'queue' => $this->queue,
        ]);
    }

    public function tags(): array
    {
        return ['email', 'email_log:'.$this->emailLogId];
    }
}
