<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Services\SystemLogReader;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ListSystemLogs extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'System Logs';

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'system-logs';

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->mountInteractsWithTable();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permission::ViewSystemLogs->value) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return 'System Logs';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(SystemLogReader::class)->listFiles())
            ->columns([
                TextColumn::make('filename')
                    ->label('Log file')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('size_human')
                    ->label('Size'),
                TextColumn::make('error_count')
                    ->label('Errors')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('modified_at')
                    ->label('Last updated')
                    ->since()
                    ->sortable(),
            ])
            ->recordUrl(fn (array $record): string => ViewSystemLog::getUrl(['file' => $record['filename']]))
            ->paginated(false);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }
}
