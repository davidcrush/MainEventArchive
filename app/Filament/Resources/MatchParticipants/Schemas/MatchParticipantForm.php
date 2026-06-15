<?php

namespace App\Filament\Resources\MatchParticipants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MatchParticipantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('match_id')
                    ->relationship('match', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->card_order} — {$record->show?->title}")
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('side')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Toggle::make('is_surprise_entrant')
                    ->label('Surprise entrant (soft spoiler)'),
                TextInput::make('placeholder_label')
                    ->label('Placeholder when spoilers off'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
