<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum EmailStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Bounced = 'bounced';
    case Failed = 'failed';
    case Retrying = 'retrying';
    case Cancelled = 'cancelled';
    case Scheduled = 'scheduled';
    case Rejected = 'rejected';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Sent,
            self::Delivered,
            self::Opened,
            self::Clicked,
            self::Bounced,
            self::Failed,
            self::Cancelled,
            self::Rejected,
        ], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Queued,
            self::Scheduled,
        ], true);
    }

    public function canRetry(): bool
    {
        return in_array($this, [
            self::Failed,
            self::Bounced,
        ], true);
    }
}
