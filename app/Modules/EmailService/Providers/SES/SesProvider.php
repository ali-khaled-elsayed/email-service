<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\SES;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Support\AbstractEmailProvider;
use Illuminate\Support\Facades\Config;

final class SesProvider extends AbstractEmailProvider
{
    protected function configureMailer(Provider $provider): string
    {
        $name = 'email_provider_'.$provider->id;
        $config = $provider->config;

        Config::set("mail.mailers.{$name}", [
            'transport' => 'ses',
            'key' => $config['key'] ?? env('AWS_ACCESS_KEY_ID'),
            'secret' => $config['secret'] ?? env('AWS_SECRET_ACCESS_KEY'),
            'region' => $config['region'] ?? env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);

        return $name;
    }

    public function healthCheck(Provider $provider): ProviderSendResultDTO
    {
        return $this->validate($provider)
            ? new ProviderSendResultDTO(success: true, response: ['checked' => 'config'])
            : new ProviderSendResultDTO(success: false, error: 'Invalid SES configuration');
    }
}
