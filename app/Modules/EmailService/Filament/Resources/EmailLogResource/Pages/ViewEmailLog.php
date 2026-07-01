<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailLogResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailLogResource;
use App\Modules\EmailService\Models\EmailLog;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        /** @var EmailLog $emailLog */
        $emailLog = $this->getRecord();

        $emailLog->loadMissing([
            'application',
            'provider',
            'fallbackProvider',
            'timelines',
            'failedAttempts.provider',
        ]);
    }
}
