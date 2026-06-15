<?php

namespace App\Filament\Resources\WrestlingMatches;

use App\Filament\Resources\WrestlingMatches\Pages\CreateWrestlingMatch;
use App\Filament\Resources\WrestlingMatches\Pages\EditWrestlingMatch;
use App\Filament\Resources\WrestlingMatches\Pages\ListWrestlingMatches;
use App\Filament\Resources\WrestlingMatches\Schemas\WrestlingMatchForm;
use App\Filament\Resources\WrestlingMatches\Tables\WrestlingMatchesTable;
use App\Models\WrestlingMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WrestlingMatchResource extends Resource
{
    protected static ?string $model = WrestlingMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return WrestlingMatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WrestlingMatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWrestlingMatches::route('/'),
            'create' => CreateWrestlingMatch::route('/create'),
            'edit' => EditWrestlingMatch::route('/{record}/edit'),
        ];
    }
}
