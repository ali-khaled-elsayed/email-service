<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Events;

use App\Modules\EmailService\Models\EmailLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EmailLog $emailLog,
    ) {}
}
