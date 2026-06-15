<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\WatchedShow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WatchedShowController extends Controller
{
    public function store(Request $request, Show $show): RedirectResponse
    {
        WatchedShow::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'show_id' => $show->id,
            ],
            ['watched_at' => now()],
        );

        return back();
    }

    public function destroy(Request $request, Show $show): RedirectResponse
    {
        WatchedShow::query()
            ->where('user_id', $request->user()->id)
            ->where('show_id', $show->id)
            ->delete();

        return back();
    }
}
