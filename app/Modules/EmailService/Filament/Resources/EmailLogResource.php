<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Enums\Permission;
use App\Modules\EmailService\Filament\Resources\EmailLogResource\Pages;
use App\Modules\EmailService\Jobs\SendEmailJob;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Services\EmailCancellationService;
use App\Modules\EmailService\Services\RetryManagerService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('application.name')->searchable(),
                TextColumn::make('subject')->limit(40)->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('type'),
                TextColumn::make('provider.name')->label('Provider'),
                TextColumn::make('retry_count'),
                TextColumn::make('sent_at')->dateTime(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(\App\Modules\EmailService\Enums\EmailStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->name])
                ),
                SelectFilter::make('application_id')
                    ->relationship('application', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn (EmailLog $record) => app(RetryManagerService::class)->manualRetry($record)),
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (EmailLog $record) => app(EmailCancellationService::class)->cancel($record)),
                Action::make('resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(fn (EmailLog $record) => SendEmailJob::dispatch($record->id)->onQueue($record->queue_name ?? 'emails-default')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailLogs::route('/'),
            'view' => Pages\ViewEmailLog::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::ViewEmailLogs->value) ?? false;
    }

    public static function canView($record): bool
    {
        return static::canViewAny();
    }
}
