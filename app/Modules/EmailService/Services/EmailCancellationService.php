<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Repositories\EmailLogRepository;
use Illuminate\Support\Facades\DB;

class EmailCancellationService
{
    public function __construct(
        private readonly EmailLogRepository $emailLogRepository,
    ) {}

    public function cancel(EmailLog $emailLog): EmailLog
    {
        if (! $emailLog->status->canCancel()) {
            throw new \RuntimeException('Email cannot be cancelled in current status: '.$emailLog->status->value);
        }

        DB::table('jobs')
            ->where('payload', 'like', '%"emailLogId":'.$emailLog->id.'%')
            ->orWhere('payload', 'like', '%email_log_id\\\\";i:'.$emailLog->id.'%')
            ->delete();

        return $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Cancelled, 'Cancelled by request');
    }
}
