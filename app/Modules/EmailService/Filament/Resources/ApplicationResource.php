<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Enums\Permission;
use App\Modules\EmailService\Enums\ApplicationStatus;
use App\Modules\EmailService\Filament\Resources\ApplicationResource\Pages;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
        $providerOptions = fn () => Provider::query()->orderBy('name')->pluck('name', 'slug');

        return $schema->components([
            Section::make('Application')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('app_key')->required()->unique(ignoreRecord: true)->maxLength(255),
                    Select::make('status')->options(ApplicationStatus::class)->required(),
                    TextInput::make('rate_limit')->numeric()->default(100)->required(),
                ])
                ->columns(2),

            Section::make('Provider routing')
                ->description('Provider selection is configured per application. API send requests do not accept a provider override.')
                ->schema([
                    Select::make('default_provider_id')
                        ->label('Default provider')
                        ->relationship('defaultProvider', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('fallback_provider_id')
                        ->label('Fallback provider')
                        ->relationship('fallbackProvider', 'name')
                        ->searchable()
                        ->preload(),
                    Select::make('settings.routing_rules.transactional')
                        ->label('Transactional emails')
                        ->options($providerOptions)
                        ->nullable()
                        ->helperText('Optional override by email type. Uses default provider when empty.'),
                    Select::make('settings.routing_rules.marketing')
                        ->label('Marketing emails')
                        ->options($providerOptions)
                        ->nullable(),
                    Select::make('settings.routing_rules.notification')
                        ->label('Notification emails')
                        ->options($providerOptions)
                        ->nullable(),
                    Select::make('settings.routing_rules.system')
                        ->label('System emails')
                        ->options($providerOptions)
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Other settings')
                ->schema([
                    TextInput::make('settings.webhook_url')
                        ->label('Webhook URL')
                        ->url()
                        ->nullable()
                        ->columnSpanFull(),
                ]),
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
                TextColumn::make('fallbackProvider.name')->label('Fallback Provider'),
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

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::ManageApplications->value) ?? false;
    }
}
