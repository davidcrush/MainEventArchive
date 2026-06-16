<?php

namespace App\Filament\Resources\Promotions\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromotionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('logo_path')
                    ->label('Logo path')
                    ->helperText('Path under resources/images/, e.g. promotions/wcw.svg')
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('founded_year')
                    ->numeric()
                    ->minValue(1800)
                    ->maxValue(2100),
                TextInput::make('active_from_year')
                    ->numeric()
                    ->minValue(1800)
                    ->maxValue(2100),
                TextInput::make('active_to_year')
                    ->numeric()
                    ->minValue(1800)
                    ->maxValue(2100)
                    ->helperText('Leave empty if still active.'),
                Toggle::make('is_active')
                    ->label('Still active'),
                TextInput::make('headquarters')
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('wikipedia_url')
                    ->label('Wikipedia URL')
                    ->url()
                    ->maxLength(255),
            ]);
    }
}
