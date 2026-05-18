<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\Mailgun;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Support\AbstractEmailProvider;
use Illuminate\Support\Facades\Config;

final class MailgunProvider extends AbstractEmailProvider
{
    protected function configureMailer(Provider $provider): string
    {
        $name = 'email_provider_'.$provider->id;
        $config = $provider->config;

        Config::set("mail.mailers.{$name}", [
            'transport' => 'mailgun',
            'domain' => $config['domain'] ?? null,
            'secret' => $config['secret'] ?? $config['api_key'] ?? null,
            'endpoint' => $config['endpoint'] ?? 'api.mailgun.net',
        ]);

        return $name;
    }

    public function healthCheck(Provider $provider): ProviderSendResultDTO
    {
        return $this->validate($provider)
            ? new ProviderSendResultDTO(success: true)
            : new ProviderSendResultDTO(success: false, error: 'Invalid Mailgun configuration');
    }
}
