<?php

namespace App\Filament\Resources\MatchParticipants\Pages;

use App\Filament\Resources\MatchParticipants\MatchParticipantResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMatchParticipant extends EditRecord
{
    protected static string $resource = MatchParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
