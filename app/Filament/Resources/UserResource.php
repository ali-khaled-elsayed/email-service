<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Permission;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->maxLength(255),
            Select::make('roles')
                ->label('Roles')
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('guard_name', 'web')->orderBy('name'),
                )
                ->multiple()
                ->preload()
                ->searchable()
                ->required(),
            CheckboxList::make('permissions')
                ->label('Direct Permissions')
                ->relationship(
                    name: 'permissions',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('guard_name', 'web')->orderBy('name'),
                )
                ->getOptionLabelFromRecordUsing(
                    fn ($record) => ucwords(str_replace('_', ' ', $record->name))
                )
                ->columns(2)
                ->searchable()
                ->helperText('Optional permissions assigned directly to this user (in addition to role permissions).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('Roles'),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Direct Permissions'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Permission::ManageUsers->value) ?? false;
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
        return static::canViewAny() && $record->id !== auth()->id();
    }
}
