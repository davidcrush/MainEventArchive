<?php

namespace App\Http\Controllers;

use App\Http\Resources\PromotionCardResource;
use App\Models\Promotion;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __invoke(): Response
    {
        $promotions = Promotion::query()
            ->listedOnIndex()
            ->withCount(['shows as published_show_count' => fn ($query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Promotions/Index', [
            'promotions' => PromotionCardResource::collection($promotions)->resolve(),
        ]);
    }
}
