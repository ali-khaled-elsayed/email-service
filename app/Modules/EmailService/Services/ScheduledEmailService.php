<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;

class ScheduledEmailService
{
    public function __construct(
        private readonly EmailDispatcherService $dispatcher,
    ) {}

    public function schedule(Application $application, SendEmailDTO $dto): EmailLog
    {
        if ($dto->scheduledAt === null) {
            throw new \InvalidArgumentException('scheduled_at is required for scheduled emails.');
        }

        return $this->dispatcher->queue($application, $dto);
    }

    public function processDueScheduled(): int
    {
        $count = 0;
        $logs = EmailLog::query()
            ->where('status', EmailStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->limit(100)
            ->get();

        foreach ($logs as $log) {
            $this->dispatcher->queue($log->application, SendEmailDTO::fromArray([
                'to' => $log->to,
                'subject' => $log->subject,
                'html' => $log->html,
                'priority' => $log->priority->value,
                'type' => $log->type->value,
            ]));
            $count++;
        }

        return $count;
    }
}
