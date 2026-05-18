<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Support;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Mail\DynamicEmailMailable;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Providers\Contracts\EmailProviderContract;
use App\Modules\EmailService\Services\AttachmentService;
use Illuminate\Support\Facades\Mail;

abstract class AbstractEmailProvider implements EmailProviderContract
{
    public function __construct(
        protected readonly AttachmentService $attachmentService,
    ) {}

    public function send(EmailLog $emailLog, Provider $provider): ProviderSendResultDTO
    {
        try {
            $mailer = $this->configureMailer($provider);
            $mailable = new DynamicEmailMailable($emailLog, $provider);

            Mail::mailer($mailer)->send($mailable);

            return new ProviderSendResultDTO(
                success: true,
                messageId: (string) $emailLog->id,
                response: ['provider' => $provider->slug],
            );
        } catch (\Throwable $e) {
            return new ProviderSendResultDTO(
                success: false,
                error: $e->getMessage(),
                retryable: $this->isRetryable($e),
            );
        }
    }

    public function validate(Provider $provider): bool
    {
        $config = $provider->config;

        return ! empty($config);
    }

    public function getQuota(Provider $provider): ?int
    {
        return $provider->quota_limit;
    }

    public function supportsAttachments(): bool
    {
        return true;
    }

    abstract protected function configureMailer(Provider $provider): string;

    protected function isRetryable(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        $retryablePatterns = [
            'timeout', 'timed out', 'connection', '429', 'rate limit',
            'temporarily', 'temporary', '503', '502', '504', 'network',
        ];

        $nonRetryablePatterns = [
            'invalid', 'blocked', 'spam', 'rejected', '550', '551',
            '553', 'mailbox', 'domain', 'malformed',
        ];

        foreach ($nonRetryablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return false;
            }
        }

        foreach ($retryablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
