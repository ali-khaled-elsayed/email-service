<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Enums\Permission;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Filament\Resources\EmailLogResource\Pages;
use App\Modules\EmailService\Jobs\SendEmailJob;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Services\EmailCancellationService;
use App\Modules\EmailService\Services\RetryManagerService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $recordTitleAttribute = 'subject';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $formatRecipients = function (mixed $state): string {
            if (blank($state)) {
                return '—';
            }

            if (is_string($state)) {
                $decoded = json_decode($state, true);
                $state = is_array($decoded) ? $decoded : [$state];
            }

            if (! is_array($state)) {
                return (string) $state;
            }

            return implode(', ', array_map(
                fn(mixed $recipient): string => is_array($recipient)
                    ? (string) ($recipient['email'] ?? reset($recipient))
                    : (string) $recipient,
                $state,
            ));
        };

        $formatJson = function (mixed $state): ?string {
            if (blank($state)) {
                return null;
            }

            if (is_string($state)) {
                $decoded = json_decode($state, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $state = $decoded;
                } else {
                    return $state;
                }
            }

            return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };

        return $schema->components([
            Section::make('Overview')
                ->columns(3)
                ->schema([
                    TextEntry::make('id')->label('Email ID'),
                    TextEntry::make('application.name')->label('Application'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn(EmailStatus $state): string => match ($state) {
                            EmailStatus::Sent, EmailStatus::Delivered, EmailStatus::Opened, EmailStatus::Clicked => 'success',
                            EmailStatus::Failed, EmailStatus::Bounced, EmailStatus::Rejected => 'danger',
                            EmailStatus::Retrying => 'warning',
                            EmailStatus::Cancelled => 'gray',
                            default => 'info',
                        }),
                    TextEntry::make('priority')->badge(),
                    TextEntry::make('type')->badge()->color('gray'),
                    TextEntry::make('queue_name')->label('Queue')->placeholder('—'),
                    TextEntry::make('provider.name')->label('Provider')->placeholder('—'),
                    TextEntry::make('fallbackProvider.name')->label('Fallback provider')->placeholder('—'),
                    TextEntry::make('retry_count')->label('Retry count'),
                    TextEntry::make('idempotency_key')->label('Idempotency key')->placeholder('—'),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('scheduled_at')->dateTime()->placeholder('—'),
                    TextEntry::make('sent_at')->dateTime()->placeholder('—'),
                    TextEntry::make('failed_at')->dateTime()->placeholder('—'),
                ]),

            Section::make('Recipients')
                ->schema([
                    TextEntry::make('subject')->columnSpanFull(),
                    TextEntry::make('to')->label('To')->formatStateUsing($formatRecipients),
                    TextEntry::make('cc')->label('CC')->formatStateUsing($formatRecipients),
                    TextEntry::make('bcc')->label('BCC')->formatStateUsing($formatRecipients),
                ])
                ->columns(1),

            Section::make('Errors')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->iconColor('danger')
                ->visible(fn(EmailLog $record): bool => filled($record->error_message)
                    || $record->failedAttempts->isNotEmpty()
                    || in_array($record->status, [EmailStatus::Failed, EmailStatus::Bounced, EmailStatus::Rejected], true))
                ->schema([
                    TextEntry::make('error_message')
                        ->label('Error message')
                        ->color('danger')
                        ->columnSpanFull()
                        ->visible(fn(EmailLog $record): bool => filled($record->error_message)),
                    RepeatableEntry::make('failedAttempts')
                        ->label('Failed attempts')
                        ->schema([
                            TextEntry::make('attempt_number')->label('#'),
                            TextEntry::make('provider.name')->label('Provider')->placeholder('—'),
                            IconEntry::make('retryable')->boolean()->label('Retryable'),
                            TextEntry::make('created_at')->dateTime()->label('When'),
                            TextEntry::make('exception')
                                ->label('Exception')
                                ->color('danger')
                                ->columnSpanFull(),
                            TextEntry::make('stack_trace')
                                ->label('Stack trace')
                                ->columnSpanFull()
                                ->visible(fn(?string $state): bool => filled($state)),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Email history')
                ->icon(Heroicon::OutlinedClock)
                ->schema([
                    RepeatableEntry::make('timelines')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('created_at')->dateTime()->label('When'),
                            TextEntry::make('old_status')->badge()->label('From')->placeholder('—'),
                            TextEntry::make('new_status')->badge()->label('To'),
                            TextEntry::make('message')->label('Message')->placeholder('—')->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->columnSpanFull()
                        ->placeholder('No status changes recorded yet.'),
                ]),

            Section::make('Template')
                ->collapsed()
                ->visible(fn(EmailLog $record): bool => filled($record->template_slug) || filled($record->template_data))
                ->schema([
                    TextEntry::make('template_slug')->label('Template slug')->placeholder('—'),
                    TextEntry::make('template_data')
                        ->label('Template data')
                        ->formatStateUsing($formatJson)
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Section::make('Provider response')
                ->collapsed()
                ->visible(fn(EmailLog $record): bool => filled($record->provider_response))
                ->schema([
                    TextEntry::make('provider_response')
                        ->formatStateUsing($formatJson)
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Section::make('Content')
                ->collapsed()
                ->schema([
                    TextEntry::make('text_content')->label('Plain text')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('html')->html()->label('HTML body')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('attachments')
                        ->formatStateUsing($formatJson)
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Section::make('Metadata')
                ->collapsed()
                ->visible(fn(EmailLog $record): bool => filled($record->meta))
                ->schema([
                    TextEntry::make('meta')->formatStateUsing($formatJson)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('application.name')->searchable(),
                TextColumn::make('subject')
                    ->limit(40)
                    ->searchable()
                    ->url(fn(EmailLog $record): string => static::getUrl('view', ['record' => $record])),
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
                        ->mapWithKeys(fn($c) => [$c->value => $c->name])
                ),
                SelectFilter::make('application_id')
                    ->relationship('application', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn(EmailLog $record): bool => $record->status->canRetry())
                    ->action(fn(EmailLog $record) => app(RetryManagerService::class)->manualRetry($record)),
                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(EmailLog $record): bool => $record->status->canCancel())
                    ->action(fn(EmailLog $record) => app(EmailCancellationService::class)->cancel($record)),
                Action::make('resend')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn(EmailLog $record): bool => !in_array($record->status, [EmailStatus::Failed, EmailStatus::Sending, EmailStatus::Retrying], true))
                    ->action(fn(EmailLog $record) => SendEmailJob::dispatch($record->id)->onQueue($record->queue_name ?? 'emails-default')),
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
