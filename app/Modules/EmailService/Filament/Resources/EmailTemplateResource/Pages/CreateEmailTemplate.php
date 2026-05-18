<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailTemplateResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;
}
