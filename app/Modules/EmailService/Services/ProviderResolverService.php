<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailType;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Repositories\ProviderRepository;

class ProviderResolverService
{
    public function __construct(
        private readonly ProviderRepository $providerRepository,
        private readonly ProviderHealthService $healthService,
    ) {}

    public function resolve(Application $application, SendEmailDTO $dto, int $retryAttempt = 0): ?Provider
    {
        if ($dto->providerSlug) {
            $override = $this->providerRepository->findBySlug($dto->providerSlug);
            if ($override?->isAvailable()) {
                return $override;
            }
        }

        $rules = $application->getSetting('routing_rules', []);
        $typeRule = $rules[$dto->type->value] ?? null;

        if ($typeRule) {
            $provider = $this->providerRepository->findBySlug((string) $typeRule);
            if ($provider?->isAvailable()) {
                return $provider;
            }
        }

        if ($retryAttempt > 0 && $application->fallbackProvider?->isAvailable()) {
            return $application->fallbackProvider;
        }

        if ($application->defaultProvider?->isAvailable()) {
            return $application->defaultProvider;
        }

        return $this->selectByWeightedBalance($dto);
    }

    private function selectByWeightedBalance(SendEmailDTO $dto): ?Provider
    {
        $providers = $this->providerRepository->getAvailableOrdered();

        if ($providers->isEmpty()) {
            return null;
        }

        if ($dto->type === EmailType::Marketing) {
            return $providers->first(fn (Provider $p) => in_array($p->type->value, ['mailgun', 'sendgrid', 'brevo'], true))
                ?? $providers->first();
        }

        $totalWeight = $providers->sum('weight');
        $random = random_int(1, max(1, $totalWeight));
        $cumulative = 0;

        foreach ($providers as $provider) {
            $cumulative += $provider->weight;
            if ($random <= $cumulative) {
                return $provider;
            }
        }

        return $providers->first();
    }
}
