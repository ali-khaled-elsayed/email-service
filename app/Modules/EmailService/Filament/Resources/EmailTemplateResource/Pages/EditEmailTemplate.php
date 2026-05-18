<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailTemplateResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
