<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\SMTP;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Support\AbstractEmailProvider;
use Illuminate\Support\Facades\Config;

final class SmtpProvider extends AbstractEmailProvider
{
    protected function configureMailer(Provider $provider): string
    {
        $name = 'email_provider_'.$provider->id;
        $config = $provider->config;

        Config::set("mail.mailers.{$name}", [
            'transport' => 'smtp',
            'host' => $config['host'] ?? 'localhost',
            'port' => (int) ($config['port'] ?? 587),
            'encryption' => $config['encryption'] ?? 'tls',
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'timeout' => (int) ($config['timeout'] ?? $provider->timeout),
        ]);

        return $name;
    }

    public function healthCheck(Provider $provider): ProviderSendResultDTO
    {
        $start = microtime(true);

        try {
            $host = $provider->config['host'] ?? null;
            $port = (int) ($provider->config['port'] ?? 587);

            if (! $host) {
                return new ProviderSendResultDTO(success: false, error: 'Missing SMTP host');
            }

            $connection = @fsockopen($host, $port, $errno, $errstr, 5);
            $latency = (int) ((microtime(true) - $start) * 1000);

            if ($connection) {
                fclose($connection);

                return new ProviderSendResultDTO(
                    success: true,
                    response: ['latency_ms' => $latency],
                );
            }

            return new ProviderSendResultDTO(
                success: false,
                error: $errstr ?: 'Connection failed',
                retryable: true,
            );
        } catch (\Throwable $e) {
            return new ProviderSendResultDTO(success: false, error: $e->getMessage(), retryable: true);
        }
    }
}
