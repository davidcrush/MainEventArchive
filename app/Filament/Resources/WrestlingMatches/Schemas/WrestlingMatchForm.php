<?php

namespace App\Filament\Resources\WrestlingMatches\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WrestlingMatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('show_id')
                    ->relationship('show', 'title')
                    ->required(),
                TextInput::make('card_order')
                    ->required()
                    ->numeric(),
                TextInput::make('match_type')
                    ->required(),
                TextInput::make('title_name'),
                Repeater::make('participants')
                    ->relationship()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('side')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_surprise_entrant')
                            ->label('Surprise entrant'),
                        TextInput::make('placeholder_label')
                            ->label('Placeholder when spoilers off'),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add participant')
                    ->orderColumn('sort_order')
                    ->columnSpanFull(),
                Toggle::make('is_surprise')
                    ->required(),
                TextInput::make('tournament_round')
                    ->label('Tournament round')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->helperText('Round 1 = opening bouts (visible with spoilers off). Round 2+ = ??? placeholders. Leave blank for non-tournament matches.'),
                Toggle::make('is_rateable')
                    ->required(),
                Toggle::make('is_ppv')
                    ->label('PPV match (visible on public card)')
                    ->default(true),
                TextInput::make('winner_side')
                    ->numeric(),
                TextInput::make('finish'),
                TextInput::make('duration_seconds')
                    ->numeric(),
                TextInput::make('timestamp_start')
                    ->numeric(),
                TextInput::make('timestamp_end')
                    ->numeric(),
                Toggle::make('title_changed')
                    ->required(),
            ]);
    }
}
