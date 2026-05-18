<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Support;

use App\Modules\EmailService\Services\EmailSettingsService;

/**
 * Access dynamic email settings (DB-backed, cached).
 */
final class EmailSettings
{
    public static function maxAttempts(): int
    {
        return app(EmailSettingsService::class)->getMaxAttempts();
    }

    /**
     * @return array<int, int>
     */
    public static function retryDelays(): array
    {
        return app(EmailSettingsService::class)->getRetryDelays();
    }

    public static function retryDelay(int $attemptNumber): int
    {
        return app(EmailSettingsService::class)->getDelay($attemptNumber);
    }
}
