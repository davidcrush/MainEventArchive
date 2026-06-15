<?php

namespace App\Filament\Resources\Shows\Pages;

use App\Enums\ShowStatus;
use App\Filament\Resources\Shows\Actions\PublishShowAction;
use App\Filament\Resources\Shows\ShowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditShow extends EditRecord
{
    protected static string $resource = ShowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishShowAction::make(function (): void {
                $this->refreshFormData([
                    'status' => ShowStatus::Published->value,
                ]);
            }),
            DeleteAction::make(),
        ];
    }
}
