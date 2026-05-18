<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ApplicationResource\Pages;

use App\Modules\EmailService\Filament\Resources\ApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
