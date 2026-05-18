<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedEmailAttempt extends Model
{
    protected $fillable = [
        'email_log_id',
        'provider_id',
        'exception',
        'stack_trace',
        'retryable',
        'attempt_number',
    ];

    protected function casts(): array
    {
        return [
            'retryable' => 'boolean',
        ];
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
