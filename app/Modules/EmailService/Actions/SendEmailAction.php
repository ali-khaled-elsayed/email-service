<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Actions;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Services\EmailDispatcherService;
use App\Modules\EmailService\Services\EmailRateLimiterService;

final class SendEmailAction
{
    public function __construct(
        private readonly EmailDispatcherService $dispatcher,
        private readonly EmailRateLimiterService $rateLimiter,
    ) {}

    public function execute(Application $application, SendEmailDTO $dto): EmailLog
    {
        if (! $application->isActive()) {
            throw new \RuntimeException('Application is not active.');
        }

        if ($this->rateLimiter->tooManyAttempts($application)) {
            throw new \RuntimeException('Rate limit exceeded. Retry in '.$this->rateLimiter->availableIn($application).' seconds.');
        }

        $this->rateLimiter->hit($application);

        return $this->dispatcher->queue($application, $dto);
    }
}
