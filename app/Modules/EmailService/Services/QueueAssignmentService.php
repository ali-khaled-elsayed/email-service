<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Enums\EmailPriority;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\EmailLog;

class QueueAssignmentService
{
    public function resolveQueue(EmailLog $emailLog): string
    {
        if ($emailLog->status === EmailStatus::Retrying) {
            return config('email_service.queues.retry');
        }

        return $emailLog->priority->queueName();
    }
}
