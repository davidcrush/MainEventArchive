<?php

namespace App\Filament\Resources\Venues\Actions;

use App\Importers\WikipediaVenueImporter;
use App\Models\Venue;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use RuntimeException;

class ReimportVenueFromWikipediaAction
{
    public static function make(?Closure $afterReimport = null): Action
    {
        return Action::make('reimportFromWikipedia')
            ->label('Re-import from Wikipedia')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Re-import venue from Wikipedia?')
            ->modalDescription('Location, capacity, and alias data will be refreshed from the venue Wikipedia page. Manual edits to those fields may be overwritten.')
            ->visible(fn (Venue $record): bool => filled($record->wikipedia_page_title))
            ->action(function (Venue $record) use ($afterReimport): void {
                try {
                    app(WikipediaVenueImporter::class)->importFromPageTitle(
                        $record->wikipedia_page_title,
                        refresh: true,
                    );
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Wikipedia import failed')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                $record->refresh();

                Notification::make()
                    ->success()
                    ->title('Venue updated from Wikipedia')
                    ->send();

                if ($afterReimport !== null) {
                    $afterReimport($record);
                }
            });
    }
}
