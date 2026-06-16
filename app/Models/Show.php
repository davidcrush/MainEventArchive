<?php

namespace App\Models;

use App\Enums\Brand;
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Observers\ShowObserver;
use Database\Factories\ShowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'promotion_id',
    'title',
    'slug',
    'date',
    'episode_number',
    'venue',
    'venue_id',
    'city',
    'show_type',
    'brand',
    'attendance',
    'tv_rating',
    'status',
    'cagematch_url',
    'source',
    'source_id',
    'source_url',
    'imported_at',
    'verified_at',
    'verified_by',
])]
#[ObservedBy([ShowObserver::class])]
class Show extends Model
{
    /** @use HasFactory<ShowFactory> */
    use HasFactory;

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(WrestlingMatch::class)->orderBy('card_order');
    }

    public function ppvMatches(): HasMany
    {
        return $this->matches()->where('is_ppv', true);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function watchlistItems(): HasMany
    {
        return $this->hasMany(WatchlistItem::class);
    }

    public function watchedShows(): HasMany
    {
        return $this->hasMany(WatchedShow::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @param  Builder<Show>  $query
     * @return Builder<Show>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ShowStatus::Published);
    }

    /**
     * @param  Builder<Show>  $query
     * @return Builder<Show>
     */
    public function scopeWithCardAggregates(Builder $query): Builder
    {
        return $query
            ->withAvg('ratings', 'stars')
            ->withCount('ratings')
            ->withExists(['videos as has_video' => fn ($q) => $q->whereNull('match_id')]);
    }

    /**
     * @param  Builder<Show>  $query
     * @return Builder<Show>
     */
    public function scopeWatchable(Builder $query): Builder
    {
        return $query->whereHas('videos', fn ($q) => $q->whereNull('match_id'));
    }

    /**
     * @param  Builder<Show>  $query
     * @return Builder<Show>
     */
    public function scopeWithVideoProvider(Builder $query, string $provider): Builder
    {
        return $query->whereHas('videos', fn ($q) => $q
            ->whereNull('match_id')
            ->where('provider', $provider));
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'show_type' => ShowType::class,
            'brand' => Brand::class,
            'status' => ShowStatus::class,
            'tv_rating' => 'decimal:1',
            'imported_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
