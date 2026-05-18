<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\Resend;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Providers\Contracts\EmailProviderContract;
use App\Modules\EmailService\Services\AttachmentService;
use Illuminate\Support\Facades\Http;

final class ResendProvider implements EmailProviderContract
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    public function send(EmailLog $emailLog, Provider $provider): ProviderSendResultDTO
    {
        try {
            $config = $provider->config;
            $payload = [
                'from' => ($config['from_name'] ?? 'Email Service').' <'.($config['from_email'] ?? 'noreply@example.com').'>',
                'to' => $emailLog->to,
                'subject' => $emailLog->subject,
                'html' => $emailLog->html,
            ];

            if ($emailLog->text_content) {
                $payload['text'] = $emailLog->text_content;
            }

            $response = Http::withToken($config['api_key'] ?? '')
                ->timeout($provider->timeout)
                ->post('https://api.resend.com/emails', $payload);

            if ($response->successful()) {
                return new ProviderSendResultDTO(
                    success: true,
                    messageId: $response->json('id'),
                    response: $response->json() ?? [],
                );
            }

            return new ProviderSendResultDTO(
                success: false,
                error: $response->body(),
                retryable: $response->status() === 429 || $response->serverError(),
            );
        } catch (\Throwable $e) {
            return new ProviderSendResultDTO(
                success: false,
                error: $e->getMessage(),
                retryable: true,
            );
        }
    }

    public function validate(Provider $provider): bool
    {
        return ! empty($provider->config['api_key']);
    }

    public function healthCheck(Provider $provider): ProviderSendResultDTO
    {
        return $this->validate($provider)
            ? new ProviderSendResultDTO(success: true)
            : new ProviderSendResultDTO(success: false, error: 'Invalid Resend configuration');
    }

    public function getQuota(Provider $provider): ?int
    {
        return $provider->quota_limit;
    }

    public function supportsAttachments(): bool
    {
        return true;
    }
}
