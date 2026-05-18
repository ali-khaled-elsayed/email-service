<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Enums;

enum ProviderType: string
{
    case Smtp = 'smtp';
    case Ses = 'ses';
    case Mailgun = 'mailgun';
    case SendGrid = 'sendgrid';
    case Postmark = 'postmark';
    case Brevo = 'brevo';
    case Resend = 'resend';
    case Custom = 'custom';
}
