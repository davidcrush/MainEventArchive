<?php

namespace App\Filament\Resources\Shows\Tables;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Filament\Resources\Shows\Actions\PublishShowAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('promotion.name')
                    ->sortable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('show_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('episode_number')
                    ->label('Episode #')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('imported_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ShowStatus::class),
                SelectFilter::make('show_type')
                    ->options(ShowType::class),
                SelectFilter::make('series')
                    ->options([
                        'nitro' => 'WCW Monday Nitro',
                        'clash' => 'Clash of the Champions',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'nitro' => $query->where('title', 'like', 'WCW Monday Nitro%'),
                        'clash' => $query->where('title', 'like', 'Clash of the Champions%'),
                        default => $query,
                    }),
                SelectFilter::make('review_queue')
                    ->label('Review queue')
                    ->options([
                        'pending' => 'Pending review',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        ($data['value'] ?? null) === 'pending',
                        fn ($q) => $q->where('status', ShowStatus::PendingReview),
                    )),
            ])
            ->recordActions([
                EditAction::make(),
                PublishShowAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
