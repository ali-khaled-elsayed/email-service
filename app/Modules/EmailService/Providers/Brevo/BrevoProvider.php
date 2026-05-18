<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\Brevo;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Support\AbstractEmailProvider;
use Illuminate\Support\Facades\Config;

final class BrevoProvider extends AbstractEmailProvider
{
    protected function configureMailer(Provider $provider): string
    {
        $name = 'email_provider_'.$provider->id;
        $config = $provider->config;

        Config::set("mail.mailers.{$name}", [
            'transport' => 'brevo',
            'key' => $config['api_key'] ?? $config['key'] ?? null,
        ]);

        return $name;
    }

    public function healthCheck(Provider $provider): ProviderSendResultDTO
    {
        return $this->validate($provider)
            ? new ProviderSendResultDTO(success: true)
            : new ProviderSendResultDTO(success: false, error: 'Invalid Brevo configuration');
    }
}
