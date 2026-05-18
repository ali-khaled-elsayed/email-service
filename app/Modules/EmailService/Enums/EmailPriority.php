<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum EmailPriority: string
{
    case High = 'high';
    case Default = 'default';
    case Low = 'low';
    case Bulk = 'bulk';

    public function queueName(): string
    {
        return match ($this) {
            self::High => config('email_service.queues.high'),
            self::Low => config('email_service.queues.low'),
            self::Bulk => config('email_service.queues.bulk'),
            default => config('email_service.queues.default'),
        };
    }
}
