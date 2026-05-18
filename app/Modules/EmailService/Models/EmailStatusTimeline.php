<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use App\Modules\EmailService\Enums\EmailStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailStatusTimeline extends Model
{
    protected $fillable = [
        'email_log_id',
        'old_status',
        'new_status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'old_status' => EmailStatus::class,
            'new_status' => EmailStatus::class,
        ];
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }
}
