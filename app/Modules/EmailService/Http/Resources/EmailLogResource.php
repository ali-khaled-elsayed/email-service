<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\EmailService\Models\EmailLog */
class EmailLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'type' => $this->type->value,
            'subject' => $this->subject,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'provider_id' => $this->provider_id,
            'retry_count' => $this->retry_count,
            'error_message' => $this->error_message,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'meta' => $this->meta,
            'timelines' => EmailStatusTimelineResource::collection($this->whenLoaded('timelines')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
