<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ProviderResource\Pages;

use App\Modules\EmailService\Filament\Resources\ProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
