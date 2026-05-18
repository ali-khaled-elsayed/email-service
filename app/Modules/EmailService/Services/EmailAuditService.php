<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class EmailAuditService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $event, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'log_name' => 'email_service',
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}
