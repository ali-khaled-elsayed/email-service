<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources\EmailLogResource\Pages;

use App\Modules\EmailService\Filament\Resources\EmailLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEmailLog extends ViewRecord
{
    protected static string $resource = EmailLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('id'),
            TextEntry::make('status'),
            TextEntry::make('subject'),
            TextEntry::make('to')->formatStateUsing(fn ($state) => implode(', ', $state ?? [])),
            TextEntry::make('error_message')->columnSpanFull(),
            TextEntry::make('html')->html()->columnSpanFull(),
        ]);
    }
}
