<?php

namespace App\Filament\Resources\Venues\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VenueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit')
                    ->helperText('Auto-generated from name when creating.'),
                TextInput::make('city')
                    ->maxLength(255),
                TextInput::make('state_province')
                    ->label('State / province')
                    ->maxLength(255),
                TextInput::make('country')
                    ->maxLength(255),
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('wikipedia_page_title')
                    ->label('Wikipedia page title')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('wikipedia_url')
                    ->label('Wikipedia URL')
                    ->url()
                    ->maxLength(255)
                    ->required(),
                DateTimePicker::make('imported_at')
                    ->disabled()
                    ->dehydrated(),
            ]);
    }
}
