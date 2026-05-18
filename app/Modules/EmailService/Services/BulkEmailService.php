<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailPriority;
use App\Modules\EmailService\Models\Application;
use Illuminate\Support\Collection;

class BulkEmailService
{
    private const CHUNK_SIZE = 50;

    public function __construct(
        private readonly EmailDispatcherService $dispatcher,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     * @return Collection<int, int>
     */
    public function dispatch(Application $application, array $recipients, array $basePayload): Collection
    {
        $ids = collect();

        foreach (array_chunk($recipients, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $recipient) {
                $data = array_merge($basePayload, [
                    'to' => [$recipient['email'] ?? $recipient],
                    'priority' => 'bulk',
                    'meta' => array_merge($basePayload['meta'] ?? [], $recipient['meta'] ?? []),
                ]);

                if (isset($recipient['variables'])) {
                    $data['template_data'] = array_merge(
                        $data['template_data'] ?? [],
                        $recipient['variables'],
                    );
                }

                $dto = SendEmailDTO::fromArray($data);
                $dto = new SendEmailDTO(
                    to: $dto->to,
                    subject: $dto->subject,
                    html: $dto->html,
                    text: $dto->text,
                    cc: $dto->cc,
                    bcc: $dto->bcc,
                    priority: EmailPriority::Bulk,
                    type: $dto->type,
                    scheduledAt: $dto->scheduledAt,
                    attachments: $dto->attachments,
                    meta: $dto->meta,
                    idempotencyKey: $dto->idempotencyKey,
                    templateSlug: $dto->templateSlug,
                    templateData: $dto->templateData,
                );

                $log = $this->dispatcher->queue($application, $dto);
                $ids->push($log->id);
            }
        }

        return $ids;
    }
}
