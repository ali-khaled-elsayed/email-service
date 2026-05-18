<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ApplicationResource\Pages;

use App\Modules\EmailService\Filament\Resources\ApplicationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;
}
