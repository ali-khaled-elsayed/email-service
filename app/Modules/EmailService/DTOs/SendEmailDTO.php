<?php

declare(strict_types=1);

namespace App\Modules\EmailService\DTOs;

use App\Modules\EmailService\Enums\EmailPriority;
use App\Modules\EmailService\Enums\EmailType;

final readonly class SendEmailDTO
{
    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     * @param  array<int, array<string, mixed>>  $attachments
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $templateData
     */
    public function __construct(
        public array $to,
        public string $subject,
        public ?string $html = null,
        public ?string $text = null,
        public array $cc = [],
        public array $bcc = [],
        public EmailPriority $priority = EmailPriority::Default,
        public EmailType $type = EmailType::Transactional,
        public ?\DateTimeInterface $scheduledAt = null,
        public array $attachments = [],
        public array $meta = [],
        public ?string $idempotencyKey = null,
        public ?string $templateSlug = null,
        public array $templateData = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            to: array_values(array_map('strval', $data['to'] ?? [])),
            subject: (string) ($data['subject'] ?? ''),
            html: $data['html'] ?? null,
            text: $data['text'] ?? $data['text_content'] ?? null,
            cc: array_values(array_map('strval', $data['cc'] ?? [])),
            bcc: array_values(array_map('strval', $data['bcc'] ?? [])),
            priority: EmailPriority::tryFrom((string) ($data['priority'] ?? 'default')) ?? EmailPriority::Default,
            type: EmailType::tryFrom((string) ($data['type'] ?? 'transactional')) ?? EmailType::Transactional,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTimeImmutable((string) $data['scheduled_at']) : null,
            attachments: $data['attachments'] ?? [],
            meta: $data['meta'] ?? [],
            idempotencyKey: $data['idempotency_key'] ?? null,
            templateSlug: $data['template'] ?? $data['template_slug'] ?? null,
            templateData: $data['template_data'] ?? [],
        );
    }
}
