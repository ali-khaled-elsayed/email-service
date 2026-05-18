<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Unhealthy = 'unhealthy';
    case Unknown = 'unknown';

    public function isAvailable(): bool
    {
        return in_array($this, [self::Healthy, self::Degraded], true);
    }
}
