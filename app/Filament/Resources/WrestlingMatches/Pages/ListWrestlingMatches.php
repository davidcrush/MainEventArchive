<?php

namespace App\Filament\Resources\WrestlingMatches\Pages;

use App\Filament\Resources\WrestlingMatches\WrestlingMatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWrestlingMatches extends ListRecords
{
    protected static string $resource = WrestlingMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
