<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Enums\Permission;
use App\Modules\EmailService\Filament\Resources\SettingsResource\Pages;
use App\Modules\EmailService\Models\EmailSetting;
use App\Modules\EmailService\Services\EmailSettingsService;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

use function Filament\Support\original_request;

class SettingsResource extends Resource
{
    protected static ?string $model = EmailSetting::class;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $modelLabel = 'Settings';

    protected static ?string $pluralModelLabel = 'Settings';

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Retry policy')
                ->description('Stored in the database and applied immediately to all email retries.')
                ->schema([
                    TextInput::make('max_attempts')
                        ->label('Number of retries')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(5)
                        ->live(debounce: 500)
                        ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                            $max = max(1, (int) ($state ?? 5));
                            $current = $get('retry_delays') ?? [];
                            $defaults = app(EmailSettingsService::class)->defaultRetryDelays();
                            $rows = [];

                            for ($i = 1; $i <= $max; $i++) {
                                $existing = collect($current)->firstWhere('attempt', $i);
                                $rows[] = [
                                    'attempt' => $i,
                                    'delay' => $existing['delay'] ?? $defaults[$i] ?? (int) (end($defaults) ?: 60),
                                ];
                            }

                            $set('retry_delays', $rows);
                        })
                        ->helperText('How many times a failed email may be retried.'),

                    Placeholder::make('retry_summary')
                        ->label('Preview')
                        ->content(function (Get $get): HtmlString {
                            $max = (int) ($get('max_attempts') ?? 5);
                            $delays = $get('retry_delays') ?? [];
                            $lines = ["<strong>Max retries:</strong> {$max}"];

                            foreach ($delays as $row) {
                                if (isset($row['attempt'], $row['delay'])) {
                                    $lines[] = "Retry #{$row['attempt']}: wait {$row['delay']} seconds";
                                }
                            }

                            return new HtmlString(implode('<br>', $lines));
                        }),

                    Repeater::make('retry_delays')
                        ->label('Retry delay per attempt')
                        ->schema([
                            TextInput::make('attempt')
                                ->label('Attempt')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make('delay')
                                ->label('Delay')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->suffix('sec'),
                        ])
                        ->columns(2)
                        ->minItems(fn (Get $get): int => max(1, (int) ($get('max_attempts') ?? 1)))
                        ->maxItems(fn (Get $get): int => max(1, (int) ($get('max_attempts') ?? 1)))
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->defaultItems(5),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'edit' => Pages\ManageSettings::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can(Permission::ManageSettings->value);
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * Filament only registers navigation for resources with an index page.
     *
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        if (! static::canAccess()) {
            return [];
        }

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn () => original_request()->routeIs(static::getRouteBaseName().'.*'))
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => EmailSetting::instance()]);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getIndexUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
    ): string {
        return static::getUrl('edit', [
            'record' => EmailSetting::instance(),
            ...$parameters,
        ], $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }
}
