<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum ApplicationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
}
