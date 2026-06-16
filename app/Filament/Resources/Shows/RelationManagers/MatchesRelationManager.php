<?php

namespace App\Filament\Resources\Shows\RelationManagers;

use App\Models\WrestlingMatch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'matches';

    protected static ?string $title = 'Card';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('card_order')
                    ->required()
                    ->numeric()
                    ->minValue(1),
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
                    ->label('Surprise match (soft spoiler)'),
                TextInput::make('tournament_round')
                    ->label('Tournament round')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->helperText('Round 1 = opening bouts (visible with spoilers off). Round 2+ = ??? placeholders. Leave blank for non-tournament matches.'),
                Toggle::make('is_rateable')
                    ->default(true),
                Toggle::make('is_ppv')
                    ->label('PPV match (visible on public card)')
                    ->default(true),
                TextInput::make('winner_side')
                    ->numeric()
                    ->label('Winner side (hard spoiler)'),
                TextInput::make('finish')
                    ->label('Finish (hard spoiler)'),
                TextInput::make('duration_seconds')
                    ->numeric()
                    ->label('Duration seconds (hard spoiler)'),
                Toggle::make('title_changed')
                    ->label('Title changed (hard spoiler)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('participants'))
            ->defaultSort('card_order')
            ->columns([
                TextColumn::make('card_order')
                    ->label('#')
                    ->sortable()
                    ->width('3rem'),
                TextColumn::make('card_visibility')
                    ->label('Card')
                    ->badge()
                    ->state(fn (WrestlingMatch $record): string => $record->is_ppv ? 'On card' : 'Pre-show')
                    ->color(fn (WrestlingMatch $record): string => $record->is_ppv ? 'success' : 'warning')
                    ->tooltip(fn (WrestlingMatch $record): ?string => $record->is_ppv
                        ? null
                        : 'Not on the public card — dark match, Main Event, Heat, or Free For All'),
                TextColumn::make('participant_line')
                    ->label('Participants')
                    ->state(fn (WrestlingMatch $record): string => $record->participantLine())
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('participants', fn (Builder $participantQuery) => $participantQuery
                            ->where('name', 'ilike', "%{$search}%"));
                    }),
                TextColumn::make('result_line')
                    ->label('Result')
                    ->state(fn (WrestlingMatch $record): string => $record->resultLine())
                    ->wrap()
                    ->color('primary'),
                TextColumn::make('formatted_duration')
                    ->label('Time')
                    ->state(fn (WrestlingMatch $record): string => $record->formattedDuration() ?? '—'),
                TextColumn::make('match_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title_name')
                    ->label('Title')
                    ->toggleable(),
                TextColumn::make('tournament_round')
                    ->label('Round')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_surprise')
                    ->boolean()
                    ->label('Surprise')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_rateable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
