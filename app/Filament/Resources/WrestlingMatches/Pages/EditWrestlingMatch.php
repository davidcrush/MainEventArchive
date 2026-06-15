<?php

namespace App\Filament\Resources\WrestlingMatches\Pages;

use App\Filament\Resources\WrestlingMatches\WrestlingMatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWrestlingMatch extends EditRecord
{
    protected static string $resource = WrestlingMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
