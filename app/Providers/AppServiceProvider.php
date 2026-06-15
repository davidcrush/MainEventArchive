<?php

namespace App\Providers;

use App\Contracts\ShowDataImporter;
use App\Importers\WikidataShowImporter;
use App\Importers\WikipediaShowImporter;
use App\Services\SpoilerContext;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ShowDataImporter::class, WikidataShowImporter::class);
        $this->app->singleton(SpoilerContext::class);
        $this->app->singleton(WikipediaShowImporter::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
