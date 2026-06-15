<?php

namespace App\Filament\Resources\MatchParticipants;

use App\Filament\Resources\MatchParticipants\Pages\CreateMatchParticipant;
use App\Filament\Resources\MatchParticipants\Pages\EditMatchParticipant;
use App\Filament\Resources\MatchParticipants\Pages\ListMatchParticipants;
use App\Filament\Resources\MatchParticipants\Schemas\MatchParticipantForm;
use App\Filament\Resources\MatchParticipants\Tables\MatchParticipantsTable;
use App\Models\MatchParticipant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MatchParticipantResource extends Resource
{
    protected static ?string $model = MatchParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return MatchParticipantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MatchParticipantsTable::configure($table);
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
            'index' => ListMatchParticipants::route('/'),
            'create' => CreateMatchParticipant::route('/create'),
            'edit' => EditMatchParticipant::route('/{record}/edit'),
        ];
    }
}
