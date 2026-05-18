<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailTemplateResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailTemplates extends ListRecords
{
    protected static string $resource = EmailTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
