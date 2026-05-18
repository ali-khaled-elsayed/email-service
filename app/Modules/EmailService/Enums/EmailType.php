<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum EmailType: string
{
    case Transactional = 'transactional';
    case Marketing = 'marketing';
    case Notification = 'notification';
    case System = 'system';
}
