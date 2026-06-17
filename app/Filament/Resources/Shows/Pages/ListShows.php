<?php

namespace App\Filament\Resources\Shows\Pages;

use App\Enums\ShowStatus;
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
        $tabs = [
            'all' => Tab::make('All'),
            'pending_review' => Tab::make('Pending review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ShowStatus::PendingReview)),
        ];

        foreach (self::promotionTabSlugs() as $slug => $label) {
            $tabs[$slug] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas(
                    'promotion',
                    fn (Builder $promotionQuery) => $promotionQuery->where('slug', $slug),
                ));
        }

        return $tabs;
    }

    /**
     * @return array<string, string>
     */
    private static function promotionTabSlugs(): array
    {
        return [
            'wwe' => 'WWE',
            'wcw' => 'WCW',
            'tna' => 'TNA',
            'aew' => 'AEW',
        ];
    }
}
