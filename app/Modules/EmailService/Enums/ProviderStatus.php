<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum ProviderStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Maintenance = 'maintenance';
}
