<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Console;

use App\Modules\EmailService\Services\ScheduledEmailService;
use Illuminate\Console\Command;

class ProcessScheduledEmailsCommand extends Command
{
    protected $signature = 'email:process-scheduled';

    protected $description = 'Process due scheduled emails';

    public function handle(ScheduledEmailService $service): int
    {
        $count = $service->processDueScheduled();
        $this->info("Processed {$count} scheduled emails.");

        return self::SUCCESS;
    }
}
