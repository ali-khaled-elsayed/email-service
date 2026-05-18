<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Console;

use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Services\ProviderHealthService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'email:health-check';

    protected $description = 'Run health checks on all email providers';

    public function handle(ProviderHealthService $healthService): int
    {
        Provider::query()->each(function (Provider $provider) use ($healthService) {
            $status = $healthService->check($provider);
            $this->line("{$provider->name}: {$status->value}");
        });

        return self::SUCCESS;
    }
}
