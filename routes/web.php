<?php

use App\Http\Controllers\AttributionController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\WatchedShowController;
use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/dashboard', fn () => redirect()->route('home'))->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/browse', BrowseController::class)->name('browse');
Route::get('/promotions', PromotionController::class)->name('promotions.index');
Route::get('/search', SearchController::class)->name('search');
Route::get('/shows/{slug}', [ShowController::class, 'show'])->name('shows.show');
Route::get('/venues/{slug}', [VenueController::class, 'show'])->name('venues.show');
Route::get('/attribution', AttributionController::class)->name('attribution');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/{show}', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{show}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');
    Route::post('/watched/{show}', [WatchedShowController::class, 'store'])->name('watched.store');
    Route::delete('/watched/{show}', [WatchedShowController::class, 'destroy'])->name('watched.destroy');
    Route::post('/ratings', [RatingController::class, 'store'])->name('ratings.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
