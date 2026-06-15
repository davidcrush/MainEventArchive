<?php

namespace App\Filament\Resources\Shows\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Videos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_primary', 'desc')
            ->columns([
                IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary'),
                TextColumn::make('provider')
                    ->badge(),
                TextColumn::make('external_id')
                    ->label('External ID')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->url(fn ($record): string => $record->url, shouldOpenInNewTab: true)
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('title')
                    ->wrap()
                    ->searchable()
                    ->limit(80),
                IconColumn::make('embeddable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_verified_at')
                    ->label('Last verified')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last synced')
                    ->dateTime()
                    ->sortable(),
            ])
            ->emptyStateHeading('No videos linked')
            ->emptyStateDescription('Run videos:sync-youtube-playlist to import YouTube links for verification before publish.')
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
