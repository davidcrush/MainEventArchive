<?php

namespace App\Filament\Resources\Venues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VenuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('state_province')
                    ->label('State / province')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('country')
                    ->toggleable(),
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('shows_count')
                    ->counts('shows')
                    ->label('Shows')
                    ->sortable(),
                TextColumn::make('wikipedia_page_title')
                    ->label('Wikipedia')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
