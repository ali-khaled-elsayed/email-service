<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\Application;
use Illuminate\Support\Facades\RateLimiter;

class EmailRateLimiterService
{
    public function tooManyAttempts(Application $application): bool
    {
        $key = 'email_app:'.$application->id;

        return RateLimiter::tooManyAttempts($key, $application->rate_limit);
    }

    public function hit(Application $application): void
    {
        RateLimiter::hit('email_app:'.$application->id, 60);
    }

    public function availableIn(Application $application): int
    {
        return RateLimiter::availableIn('email_app:'.$application->id);
    }
}
