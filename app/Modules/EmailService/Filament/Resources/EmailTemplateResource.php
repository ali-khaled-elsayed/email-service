<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Filament\Resources;

use App\Modules\EmailService\Filament\Resources\EmailTemplateResource\Pages;
use App\Modules\EmailService\Models\EmailTemplate;
use BackedEnum;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Email Service';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('application_id')->relationship('application', 'name')->required(),
            TextInput::make('name')->required(),
            TextInput::make('slug')->required(),
            TextInput::make('subject')->required(),
            RichEditor::make('html_template')->required()->columnSpanFull(),
            TagsInput::make('variables'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('application.name'),
            TextColumn::make('name')->searchable(),
            TextColumn::make('slug'),
            TextColumn::make('subject')->limit(40),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
