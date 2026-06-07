<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Mail;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Services\AttachmentService;
use App\Modules\EmailService\Services\HtmlEmailRendererService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class DynamicEmailMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly EmailLog $emailLog,
        public readonly Provider $provider,
    ) {}

    public function envelope(): Envelope
    {
        $config = $this->provider->config;
        $fromEmail = $config['from_email'] ?? config('mail.from.address');
        $fromName = $config['from_name'] ?? config('mail.from.name');

        return new Envelope(
            from: new Address($fromEmail, $fromName),
            to: array_map(fn (string $email) => new Address($email), $this->emailLog->to),
            cc: array_map(fn (string $email) => new Address($email), $this->emailLog->cc ?? []),
            bcc: array_map(fn (string $email) => new Address($email), $this->emailLog->bcc ?? []),
            subject: $this->emailLog->subject,
        );
    }

    public function content(): Content
    {
        $renderer = app(HtmlEmailRendererService::class);

        return new Content(
            htmlString: $renderer->render($this->emailLog),
        );
    }

    /**
     * Laravel Content::text expects a Blade view name, not raw plain text.
     *
     * @return string|array<string, \Illuminate\Contracts\Support\Htmlable|string|null>
     */
    protected function buildView()
    {
        if (! isset($this->html)) {
            return parent::buildView();
        }

        return array_filter([
            'html' => new HtmlString($this->html),
            'text' => filled($this->emailLog->text_content)
                ? new HtmlString($this->emailLog->text_content)
                : null,
        ]);
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return app(AttachmentService::class)->resolveForMailable($this->emailLog);
    }
}
