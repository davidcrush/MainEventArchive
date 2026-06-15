<?php

namespace App\Filament\Resources\MatchParticipants\Pages;

use App\Filament\Resources\MatchParticipants\MatchParticipantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMatchParticipants extends ListRecords
{
    protected static string $resource = MatchParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
