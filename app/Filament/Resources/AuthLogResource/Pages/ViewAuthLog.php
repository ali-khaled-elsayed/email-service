<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuthLogResource\Pages;

use App\Filament\Resources\AuthLogResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAuthLog extends ViewRecord
{
    protected static string $resource = AuthLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Authentication event')
                ->columns(2)
                ->schema([
                    TextEntry::make('event')->badge()->color(fn ($state) => $state->color())->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('created_at')->label('Time')->dateTime(),
                    TextEntry::make('user.name')->label('User')->placeholder('—'),
                    TextEntry::make('email')->label('Email')->placeholder('—'),
                    TextEntry::make('ip_address')->label('IP address')->placeholder('—'),
                    TextEntry::make('user_agent')->label('User agent')->placeholder('—')->columnSpanFull(),
                    KeyValueEntry::make('metadata')
                        ->label('Details')
                        ->columnSpanFull()
                        ->visible(fn ($record) => filled($record->metadata)),
                ]),
        ]);
    }
}
