<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ProviderResource\Pages;

use App\Modules\EmailService\Filament\Resources\ProviderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['config'] = $record->config;

        return $data;
    }
}
