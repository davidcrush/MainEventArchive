<?php

namespace App\Filament\Resources\Shows\Actions;

use App\Enums\ShowStatus;
use App\Models\Show;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PublishShowAction
{
    public static function make(?Closure $afterPublish = null): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publish this show?')
            ->modalDescription('It will appear on the public site. Save any other edits first.')
            ->visible(fn (Show $record): bool => $record->status !== ShowStatus::Published)
            ->action(function (Show $record) use ($afterPublish): void {
                static::publish($record);

                if ($afterPublish !== null) {
                    $afterPublish($record);
                }
            });
    }

    public static function publish(Show $record): void
    {
        $record->update([
            'status' => ShowStatus::Published,
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        Notification::make()
            ->success()
            ->title('Published')
            ->body('This show is now live on the public site.')
            ->send();
    }
}
