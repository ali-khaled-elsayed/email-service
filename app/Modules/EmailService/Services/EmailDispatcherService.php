<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Jobs\SendEmailJob;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Providers\ProviderFactory;
use App\Modules\EmailService\Repositories\EmailLogRepository;

class EmailDispatcherService
{
    public function __construct(
        private readonly EmailLogRepository $emailLogRepository,
        private readonly ProviderResolverService $providerResolver,
        private readonly QueueAssignmentService $queueAssignment,
        private readonly EmailTemplateService $templateService,
        private readonly AttachmentService $attachmentService,
        private readonly EmailAuditService $auditService,
        private readonly ProviderFactory $providerFactory,
        private readonly ProviderFailoverService $failoverService,
        private readonly RetryManagerService $retryManager,
        private readonly EmailMetricsService $metricsService,
    ) {}

    public function queue(Application $application, SendEmailDTO $dto): EmailLog
    {
        if ($dto->idempotencyKey) {
            $existing = $this->emailLogRepository->findByIdempotencyKey($dto->idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        if ($dto->templateSlug) {
            $rendered = $this->templateService->render($application, $dto->templateSlug, $dto->templateData);
            $dto = new SendEmailDTO(
                to: $dto->to,
                subject: $rendered['subject'],
                html: $rendered['html'],
                text: $dto->text,
                cc: $dto->cc,
                bcc: $dto->bcc,
                priority: $dto->priority,
                type: $dto->type,
                scheduledAt: $dto->scheduledAt,
                attachments: $dto->attachments,
                meta: $dto->meta,
                idempotencyKey: $dto->idempotencyKey,
                templateSlug: $dto->templateSlug,
                templateData: $dto->templateData,
            );
        }

        $attachments = $this->attachmentService->store($dto->attachments);
        $dtoWithAttachments = new SendEmailDTO(
            to: $dto->to,
            subject: $dto->subject,
            html: $dto->html,
            text: $dto->text,
            cc: $dto->cc,
            bcc: $dto->bcc,
            priority: $dto->priority,
            type: $dto->type,
            scheduledAt: $dto->scheduledAt,
            attachments: $attachments,
            meta: $dto->meta,
            idempotencyKey: $dto->idempotencyKey,
            templateSlug: $dto->templateSlug,
            templateData: $dto->templateData,
        );

        $provider = $this->providerResolver->resolve($application, $dtoWithAttachments);
        $fallback = $application->fallbackProvider;

        $emailLog = $this->emailLogRepository->createFromDto(
            $application,
            $dtoWithAttachments,
            $provider,
            $fallback,
            $this->queueAssignment->resolveQueue(
                new EmailLog(['priority' => $dto->priority, 'status' => EmailStatus::Pending])
            ),
        );

        $this->auditService->log('email.queued', 'Email queued for delivery', $emailLog);
        $this->dispatchJob($emailLog);

        return $emailLog;
    }

    public function process(int $emailLogId): void
    {
        $emailLog = $this->emailLogRepository->findById($emailLogId);

        if (! $emailLog || $emailLog->status === EmailStatus::Cancelled) {
            return;
        }

        $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Processing);
        $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Sending);

        $provider = $emailLog->provider ?? $this->providerResolver->resolve(
            $emailLog->application,
            new SendEmailDTO(
                to: $emailLog->to,
                subject: $emailLog->subject,
                html: $emailLog->html,
                type: $emailLog->type,
            ),
            $emailLog->retry_count,
        );

        if (! $provider) {
            $this->retryManager->scheduleRetry($emailLog, 'No available provider', true, 0);

            return;
        }

        $emailLog->update(['provider_id' => $provider->id]);
        $adapter = $this->providerFactory->make($provider->type);
        $result = $adapter->send($emailLog, $provider);

        if ($result->success) {
            $emailLog->update([
                'sent_at' => now(),
                'provider_response' => $result->response,
            ]);
            $provider->increment('quota_used');
            $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Sent, 'Email sent successfully');
            $this->metricsService->increment('sent', $emailLog);
            $this->dispatchWebhook($emailLog, 'sent');

            return;
        }

        $nextProvider = $this->failoverService->getNextProvider($emailLog, $provider);

        if ($nextProvider && $result->retryable) {
            $emailLog->update(['provider_id' => $nextProvider->id]);
            $failoverResult = $this->providerFactory->make($nextProvider->type)->send($emailLog, $nextProvider);

            if ($failoverResult->success) {
                $emailLog->update(['sent_at' => now(), 'provider_response' => $failoverResult->response]);
                $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Sent, 'Sent via failover provider');
                $this->metricsService->increment('sent', $emailLog);

                return;
            }
        }

        $this->metricsService->increment('failed', $emailLog);
        $this->retryManager->scheduleRetry(
            $emailLog,
            $result->error ?? 'Unknown error',
            $result->retryable,
            $provider->id,
        );
    }

    private function dispatchJob(EmailLog $emailLog): void
    {
        $queue = $this->queueAssignment->resolveQueue($emailLog);
        $emailLog->update(['queue_name' => $queue, 'status' => EmailStatus::Queued]);
        $this->emailLogRepository->updateStatus($emailLog, EmailStatus::Queued);

        $job = SendEmailJob::dispatch($emailLog->id)->onQueue($queue);

        if ($emailLog->scheduled_at && $emailLog->scheduled_at->isFuture()) {
            $job->delay($emailLog->scheduled_at);
        }
    }

    private function dispatchWebhook(EmailLog $emailLog, string $event): void
    {
        $url = $emailLog->application->getSetting('webhook_url');
        if (! $url) {
            return;
        }

        \Illuminate\Support\Facades\Http::timeout(10)->post($url, [
            'event' => $event,
            'email_log_id' => $emailLog->id,
            'status' => $emailLog->status->value,
            'meta' => $emailLog->meta,
        ]);
    }
}
