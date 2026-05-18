<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Modules\EmailService\Enums\HealthStatus;
use App\Modules\EmailService\Enums\ProviderStatus;
use App\Modules\EmailService\Enums\ProviderType;
use App\Modules\EmailService\Filament\Resources\ProviderResource\Pages;
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

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('type')->options(ProviderType::class)->required(),
            Select::make('status')->options(ProviderStatus::class)->required(),
            TextInput::make('priority')->numeric()->default(100),
            TextInput::make('weight')->numeric()->default(1),
            TextInput::make('quota_limit')->numeric()->label('Daily Quota'),
            TextInput::make('timeout')->numeric()->default(30),
            KeyValue::make('config')
                ->label('Provider Config (encrypted at rest)')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('type')->badge(),
                TextColumn::make('health_status')->badge()
                    ->color(fn (HealthStatus $state) => match ($state) {
                        HealthStatus::Healthy => 'success',
                        HealthStatus::Degraded => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('quota_used')->label('Used'),
                TextColumn::make('quota_limit')->label('Limit'),
                TextColumn::make('priority')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
