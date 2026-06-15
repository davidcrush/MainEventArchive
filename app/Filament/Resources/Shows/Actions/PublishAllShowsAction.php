<?php

namespace App\Filament\Resources\Shows\Actions;

use App\Enums\ShowStatus;
use App\Models\Show;
use App\Services\BrowseCache;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PublishAllShowsAction
{
    public static function make(): Action
    {
        return Action::make('publishAll')
            ->label('Publish all')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Publish all pending shows?')
            ->modalDescription(fn (): string => sprintf(
                'This will publish %d show(s) to the public site. Draft shows are not included.',
                static::pendingReviewCount(),
            ))
            ->visible(fn (): bool => static::pendingReviewCount() > 0)
            ->action(function (): void {
                $count = static::publishAll();

                Notification::make()
                    ->success()
                    ->title('Published')
                    ->body(sprintf('Published %d show(s).', $count))
                    ->send();
            });
    }

    public static function pendingReviewCount(): int
    {
        return Show::query()
            ->where('status', ShowStatus::PendingReview)
            ->count();
    }

    public static function publishAll(): int
    {
        $count = static::pendingReviewCount();

        if ($count === 0) {
            return 0;
        }

        Show::query()
            ->where('status', ShowStatus::PendingReview)
            ->update([
                'status' => ShowStatus::Published,
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);

        BrowseCache::invalidate();

        return $count;
    }
}
