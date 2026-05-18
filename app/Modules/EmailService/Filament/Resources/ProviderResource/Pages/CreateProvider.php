<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\ProviderResource\Pages;

use App\Modules\EmailService\Filament\Resources\ProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProvider extends CreateRecord
{
    protected static string $resource = ProviderResource::class;
}
