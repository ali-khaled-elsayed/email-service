<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\Resources\RoleResource\Pages;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = \Spatie\Permission\Models\Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 101;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->disabled(fn ($record) => $record && in_array($record->name, \App\Enums\Role::values(), true)),
            Hidden::make('guard_name')
                ->default('web')
                ->dehydrated(),
            CheckboxList::make('permissions')
                ->label('Permissions')
                ->relationship(
                    name: 'permissions',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('guard_name', 'web')->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => ucwords(str_replace('_', ' ', $record->name))
                )
                ->columns(2)
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions'),
                TextColumn::make('created_at')->dateTime(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::ManageRoles->value) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny() && ! in_array($record->name, \App\Enums\Role::values(), true);
    }
}
