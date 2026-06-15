<?php

namespace App\Filament\Resources\Shows\Schemas;

use App\Enums\Brand;
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ShowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('promotion_id')
                    ->relationship('promotion', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->disabledOn('edit'),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('venue')
                    ->maxLength(255),
                TextInput::make('city')
                    ->maxLength(255),
                Select::make('show_type')
                    ->options(ShowType::class)
                    ->required(),
                Select::make('brand')
                    ->options(Brand::class),
                TextInput::make('attendance')
                    ->numeric(),
                Select::make('status')
                    ->options(ShowStatus::class)
                    ->required(),
                TextInput::make('cagematch_url')
                    ->url()
                    ->maxLength(255)
                    ->label('Cagematch URL'),
                TextInput::make('source')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('source_id')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('source_url')
                    ->url()
                    ->disabled()
                    ->dehydrated(),
            ]);
    }
}
