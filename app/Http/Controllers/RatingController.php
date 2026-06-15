<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rateable_type' => ['required', Rule::in(['show', 'match'])],
            'rateable_id' => ['required', 'integer'],
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $rateableType = $validated['rateable_type'] === 'show' ? Show::class : WrestlingMatch::class;

        Rating::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'rateable_type' => $rateableType,
                'rateable_id' => $validated['rateable_id'],
            ],
            ['stars' => $validated['stars']],
        );

        return back();
    }
}
