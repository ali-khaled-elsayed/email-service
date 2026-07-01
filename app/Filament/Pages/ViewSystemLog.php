<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Services\SystemLogReader;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ViewSystemLog extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'system-logs/{file}';

    public string $file = '';

    public function mount(string $file): void
    {
        abort_unless(static::canAccess(), 403);

        $this->file = basename($file);
        app(SystemLogReader::class)->resolveFilePath($this->file);
        $this->mountInteractsWithTable();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::ViewSystemLogs->value) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'System Log: '.$this->file;
    }

    protected function getHeaderActions(): array
    {
        $summary = app(SystemLogReader::class)->getFileSummary($this->file);

        return [
            Action::make('back')
                ->label('Back to log files')
                ->url(ListSystemLogs::getUrl())
                ->color('gray'),
            Action::make('summary')
                ->label($summary['error_count'].' errors · '.$summary['size_human'])
                ->disabled()
                ->color($summary['error_count'] > 0 ? 'danger' : 'gray'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $level = $this->tableFilters['level'] ?? null;

                if (is_array($level)) {
                    $level = $level['value'] ?? null;
                }

                return app(SystemLogReader::class)->readEntries(
                    $this->file,
                    is_string($level) ? $level : null,
                    $this->getTableSearch(),
                );
            })
            ->columns([
                TextColumn::make('datetime')
                    ->label('Time')
                    ->sortable(),
                TextColumn::make('level')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => 'danger',
                        'WARNING' => 'warning',
                        'INFO', 'NOTICE' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('environment')
                    ->label('Env'),
                TextColumn::make('message')
                    ->limit(100)
                    ->searchable()
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        'ERROR' => 'Error',
                        'WARNING' => 'Warning',
                        'INFO' => 'Info',
                        'DEBUG' => 'Debug',
                    ]),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Log entry details')
                    ->schema([
                        Section::make()
                            ->schema([
                                TextEntry::make('datetime')->label('Time'),
                                TextEntry::make('level')->badge(),
                                TextEntry::make('environment')->label('Environment'),
                                TextEntry::make('message')->columnSpanFull(),
                                TextEntry::make('context')
                                    ->label('Stack trace / context')
                                    ->columnSpanFull()
                                    ->visible(fn (?string $state): bool => filled($state)),
                            ])
                            ->columns(2),
                    ])
                    ->fillForm(fn (array $record): array => $record),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([25, 50, 100]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }
}
