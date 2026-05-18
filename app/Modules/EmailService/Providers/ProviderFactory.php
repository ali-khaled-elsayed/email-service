<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers;

use App\Modules\EmailService\Enums\ProviderType;
use App\Modules\EmailService\Exceptions\UnsupportedProviderException;
use App\Modules\EmailService\Providers\Contracts\EmailProviderContract;
use App\Modules\EmailService\Providers\Brevo\BrevoProvider;
use App\Modules\EmailService\Providers\Mailgun\MailgunProvider;
use App\Modules\EmailService\Providers\Postmark\PostmarkProvider;
use App\Modules\EmailService\Providers\Resend\ResendProvider;
use App\Modules\EmailService\Providers\SendGrid\SendGridProvider;
use App\Modules\EmailService\Providers\SES\SesProvider;
use App\Modules\EmailService\Providers\SMTP\SmtpProvider;

final class ProviderFactory
{
    public function make(ProviderType $type): EmailProviderContract
    {
        return match ($type) {
            ProviderType::Smtp, ProviderType::Custom => app(SmtpProvider::class),
            ProviderType::Ses => app(SesProvider::class),
            ProviderType::Mailgun => app(MailgunProvider::class),
            ProviderType::SendGrid => app(SendGridProvider::class),
            ProviderType::Postmark => app(PostmarkProvider::class),
            ProviderType::Brevo => app(BrevoProvider::class),
            ProviderType::Resend => app(ResendProvider::class),
            default => throw new UnsupportedProviderException("Provider type [{$type->value}] is not supported."),
        };
    }
}
