<?php

namespace App\Filament\Resources\Venues\RelationManagers;

use App\Filament\Resources\Shows\ShowResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShowsRelationManager extends RelationManager
{
    protected static string $relationship = 'shows';

    protected static ?string $title = 'Shows at this venue';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => ShowResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('promotion.name')
                    ->label('Promotion')
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
