<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Repositories\ProviderRepository;

class ProviderFailoverService
{
    public function __construct(
        private readonly ProviderRepository $providerRepository,
    ) {}

    public function getNextProvider(EmailLog $emailLog, Provider $failedProvider): ?Provider
    {
        if ($emailLog->fallback_provider_id && $emailLog->fallback_provider_id !== $failedProvider->id) {
            $fallback = $this->providerRepository->findById((int) $emailLog->fallback_provider_id);
            if ($fallback?->isAvailable()) {
                return $fallback;
            }
        }

        return $this->providerRepository
            ->getAvailableOrdered()
            ->first(fn (Provider $p) => $p->id !== $failedProvider->id);
    }
}
