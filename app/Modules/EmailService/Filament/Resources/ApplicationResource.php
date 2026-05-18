<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Modules\EmailService\Enums\ApplicationStatus;
use App\Modules\EmailService\Filament\Resources\ApplicationResource\Pages;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('app_key')->required()->unique(ignoreRecord: true)->maxLength(255),
            Select::make('status')->options(ApplicationStatus::class)->required(),
            Select::make('default_provider_id')
                ->label('Default Provider')
                ->relationship('defaultProvider', 'name')
                ->searchable()
                ->preload(),
            Select::make('fallback_provider_id')
                ->label('Fallback Provider')
                ->options(fn () => Provider::query()->pluck('name', 'id'))
                ->searchable(),
            TextInput::make('rate_limit')->numeric()->default(100)->required(),
            KeyValue::make('settings')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('app_key')->copyable()->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('defaultProvider.name')->label('Default Provider'),
                TextColumn::make('rate_limit'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
