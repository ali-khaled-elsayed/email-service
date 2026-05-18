<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use App\Modules\EmailService\Enums\EmailPriority;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Enums\EmailType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'provider_id',
        'fallback_provider_id',
        'status',
        'priority',
        'type',
        'subject',
        'to',
        'cc',
        'bcc',
        'html',
        'text_content',
        'attachments',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'retry_count',
        'error_message',
        'provider_response',
        'queue_name',
        'idempotency_key',
        'template_slug',
        'template_data',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailStatus::class,
            'priority' => EmailPriority::class,
            'type' => EmailType::class,
            'to' => 'array',
            'cc' => 'array',
            'bcc' => 'array',
            'attachments' => 'array',
            'provider_response' => 'array',
            'template_data' => 'array',
            'meta' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'fallback_provider_id');
    }

    public function failedAttempts(): HasMany
    {
        return $this->hasMany(FailedEmailAttempt::class);
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(EmailStatusTimeline::class);
    }

    protected static function newFactory(): \Database\Factories\EmailLogFactory
    {
        return \Database\Factories\EmailLogFactory::new();
    }
}
