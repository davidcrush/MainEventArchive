<?php

namespace App\Filament\Resources\Venues\Pages;

use App\Filament\Resources\Venues\VenueResource;
use App\Services\VenueSlugGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateVenue extends CreateRecord
{
    protected static string $resource = VenueResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! filled($data['slug'] ?? null)) {
            $data['slug'] = app(VenueSlugGenerator::class)->generate($data['name']);
        }

        if (! filled($data['imported_at'] ?? null)) {
            $data['imported_at'] = now();
        }

        return $data;
    }
}
