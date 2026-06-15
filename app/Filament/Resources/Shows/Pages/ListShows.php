<?php

namespace App\Filament\Resources\Shows\Pages;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Filament\Resources\Shows\Actions\PublishAllShowsAction;
use App\Filament\Resources\Shows\ShowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListShows extends ListRecords
{
    protected static string $resource = ShowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishAllShowsAction::make(),
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending_review' => Tab::make('Pending review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShowStatus::PendingReview)),
            'nitro' => Tab::make('Nitro')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('title', 'like', 'WCW Monday Nitro%')),
            'tv' => Tab::make('TV')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('show_type', ShowType::Tv)),
        ];
    }
}
