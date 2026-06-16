<?php

namespace App\Filament\Resources\Shows\RelationManagers;

use App\Models\Video;
use App\Services\Streaming\NetflixUrlParser;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
        return $schema
            ->components([
                TextInput::make('url')
                    ->label('Netflix URL or title ID')
                    ->helperText('Paste a Netflix watch/title URL or numeric title ID.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('title')
                    ->label('Source title (optional)')
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('Primary Netflix link')
                    ->default(true),
            ]);
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
            ->headerActions([
                CreateAction::make()
                    ->label('Add Netflix link')
                    ->mutateFormDataUsing(fn (array $data): array => $this->mutateNetflixFormData($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Video $record): bool => $record->provider === 'netflix')
                    ->mutateFormDataUsing(fn (array $data): array => $this->mutateNetflixFormData($data)),
                DeleteAction::make()
                    ->visible(fn (Video $record): bool => $record->provider === 'netflix'),
            ])
            ->emptyStateHeading('No videos linked')
            ->emptyStateDescription('Add a Netflix deep link, run videos:import-netflix, or sync YouTube playlists.')
            ->paginated([10, 25, 50]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateNetflixFormData(array $data): array
    {
        $reference = app(NetflixUrlParser::class)->parse((string) ($data['url'] ?? ''));

        $data['provider'] = 'netflix';
        $data['external_id'] = $reference['external_id'];
        $data['url'] = $reference['url'];
        $data['match_id'] = null;
        $data['embeddable'] = false;
        $data['last_verified_at'] = now();

        return $data;
    }
}
