<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailLogResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailLogs extends ListRecords
{
    protected static string $resource = EmailLogResource::class;
}
