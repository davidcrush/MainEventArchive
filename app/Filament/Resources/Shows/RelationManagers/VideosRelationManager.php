<?php

namespace App\Filament\Resources\Shows\RelationManagers;

use App\Services\YouTube\YouTubeUrlParser;
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
use InvalidArgumentException;

class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'videos';

    protected static ?string $title = 'Videos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('url')
                    ->label('YouTube URL or video ID')
                    ->helperText('Paste a YouTube watch/youtu.be URL or 11-character video ID.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('title')
                    ->label('Source title (optional)')
                    ->maxLength(255),
                Toggle::make('is_primary')
                    ->label('Primary link')
                    ->helperText('When multiple YouTube links exist, the primary link is preferred.')
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
                    ->label('Add YouTube link')
                    ->mutateFormDataUsing(fn (array $data): array => $this->mutateVideoFormData($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->mutateVideoFormData($data)),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No videos linked')
            ->emptyStateDescription('Add a YouTube link manually or sync YouTube playlists.')
            ->paginated([10, 25, 50]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutateVideoFormData(array $data): array
    {
        try {
            $reference = app(YouTubeUrlParser::class)->parse((string) ($data['url'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        }

        $data['provider'] = 'youtube';
        $data['external_id'] = $reference['external_id'];
        $data['url'] = $reference['url'];
        $data['match_id'] = null;
        $data['embeddable'] = true;
        $data['last_verified_at'] = now();

        return $data;
    }
}
