<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ApplicationResource\Pages;

use App\Modules\EmailService\Filament\Resources\ApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
