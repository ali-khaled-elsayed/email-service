<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\EmailService\Models\EmailStatusTimeline */
class EmailStatusTimelineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'old_status' => $this->old_status?->value,
            'new_status' => $this->new_status->value,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
