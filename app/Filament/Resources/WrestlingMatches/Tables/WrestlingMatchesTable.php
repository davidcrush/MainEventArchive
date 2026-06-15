<?php

namespace App\Filament\Resources\WrestlingMatches\Tables;

use App\Models\WrestlingMatch;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WrestlingMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('participants'))
            ->columns([
                TextColumn::make('show.title')
                    ->searchable(),
                TextColumn::make('card_order')
                    ->label('#')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('card_visibility')
                    ->label('Card')
                    ->badge()
                    ->state(fn (WrestlingMatch $record): string => $record->is_ppv ? 'On card' : 'Pre-show')
                    ->color(fn (WrestlingMatch $record): string => $record->is_ppv ? 'success' : 'warning')
                    ->tooltip(fn (WrestlingMatch $record): ?string => $record->is_ppv
                        ? null
                        : 'Not on the public card — dark match or Main Event'),
                TextColumn::make('participant_line')
                    ->label('Participants')
                    ->state(fn (WrestlingMatch $record): string => $record->participantLine())
                    ->wrap(),
                TextColumn::make('result_line')
                    ->label('Result')
                    ->state(fn (WrestlingMatch $record): string => $record->resultLine())
                    ->wrap(),
                TextColumn::make('formatted_duration')
                    ->label('Time')
                    ->state(fn (WrestlingMatch $record): string => $record->formattedDuration() ?? '—'),
                TextColumn::make('match_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_surprise')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_rateable')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
