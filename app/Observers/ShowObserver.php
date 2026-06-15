<?php

namespace App\Observers;

use App\Enums\ShowStatus;
use App\Models\Show;
use App\Services\BrowseCache;

class ShowObserver
{
    public function updated(Show $show): void
    {
        if ($show->wasChanged('status') && $show->status === ShowStatus::Published) {
            BrowseCache::invalidate();
        }
    }
}
