<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Repositories;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;

class EmailLogRepository
{
    private const MAX_TIMELINE_MESSAGE_LENGTH = 255;

    public function findById(int $id): ?EmailLog
    {
        return EmailLog::query()->with(['application', 'provider', 'timelines'])->find($id);
    }

    public function findByIdempotencyKey(string $key): ?EmailLog
    {
        return EmailLog::query()->where('idempotency_key', $key)->first();
    }

    public function createFromDto(Application $application, SendEmailDTO $dto, ?Provider $provider, ?Provider $fallback, string $queueName): EmailLog
    {
        $status = $dto->scheduledAt !== null && $dto->scheduledAt > new \DateTimeImmutable
            ? EmailStatus::Scheduled
            : EmailStatus::Pending;

        return EmailLog::query()->create([
            'application_id' => $application->id,
            'provider_id' => $provider?->id,
            'fallback_provider_id' => $fallback?->id,
            'status' => $status,
            'priority' => $dto->priority,
            'type' => $dto->type,
            'subject' => $dto->subject,
            'to' => $dto->to,
            'cc' => $dto->cc,
            'bcc' => $dto->bcc,
            'html' => $dto->html,
            'text_content' => $dto->text,
            'attachments' => $dto->attachments,
            'scheduled_at' => $dto->scheduledAt,
            'queue_name' => $queueName,
            'idempotency_key' => $dto->idempotencyKey,
            'template_slug' => $dto->templateSlug,
            'template_data' => $dto->templateData,
            'meta' => $dto->meta,
        ]);
    }

    public function updateStatus(EmailLog $emailLog, EmailStatus $status, ?string $message = null): EmailLog
    {
        $oldStatus = $emailLog->status;

        $emailLog->update(['status' => $status]);

        $emailLog->timelines()->create([
            'old_status' => $oldStatus,
            'new_status' => $status,
            'message' => $this->truncateTimelineMessage($message),
        ]);

        return $emailLog->fresh();
    }

    private function truncateTimelineMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return null;
        }

        return mb_substr($message, 0, self::MAX_TIMELINE_MESSAGE_LENGTH);
    }
}
