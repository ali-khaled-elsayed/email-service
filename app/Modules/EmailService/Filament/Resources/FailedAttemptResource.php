<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Enums\Permission;
use App\Modules\EmailService\Filament\Resources\FailedAttemptResource\Pages;
use App\Modules\EmailService\Models\FailedEmailAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FailedAttemptResource extends Resource
{
    protected static ?string $model = FailedEmailAttempt::class;

    protected static ?string $navigationLabel = 'Failed Attempts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email_log_id')->label('Email ID')->sortable(),
                TextColumn::make('provider.name'),
                TextColumn::make('attempt_number'),
                IconColumn::make('retryable')->boolean(),
                TextColumn::make('exception')->limit(60),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFailedAttempts::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::ViewFailedAttempts->value) ?? false;
    }
}
