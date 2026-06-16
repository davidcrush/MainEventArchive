<?php

namespace App\Http\Resources;

use App\Models\MatchParticipant;
use App\Models\WrestlingMatch;
use App\Services\SpoilerContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WrestlingMatch */
class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $spoilers = app(SpoilerContext::class);

        if (! $spoilers->isEnabled() && $this->is_surprise) {
            return [];
        }

        $data = [
            'id' => $this->id,
            'card_order' => $this->card_order,
            'match_type' => $this->match_type,
            'title_name' => $this->title_name,
            'is_rateable' => $this->is_rateable,
            'rating_average' => $this->when(isset($this->ratings_avg_stars), round((float) $this->ratings_avg_stars, 1)),
            'rating_count' => $this->when(isset($this->ratings_count), (int) $this->ratings_count),
        ];

        if (! $spoilers->isEnabled() && $this->match_type === 'battle_royal') {
            $data['participant_line'] = $this->spoilerSafeParticipantLine();
        } else {
            $data['participants'] = $this->whenLoaded(
                'participants',
                fn () => $this->participants
                    ->map(fn (MatchParticipant $participant) => $this->formatParticipant($participant, $spoilers))
                    ->values()
                    ->all(),
            );
        }

        if ($spoilers->isEnabled()) {
            $data['winner_side'] = $this->winner_side;
            $data['finish'] = $this->finish;
            $data['duration_seconds'] = $this->duration_seconds;
            $data['timestamp_start'] = $this->timestamp_start;
            $data['timestamp_end'] = $this->timestamp_end;
            $data['title_changed'] = $this->title_changed;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatParticipant(MatchParticipant $participant, SpoilerContext $spoilers): array
    {
        if (! $spoilers->isEnabled() && $participant->is_surprise_entrant) {
            return [
                'id' => $participant->id,
                'name' => $participant->placeholder_label ?? 'TBA',
                'side' => $participant->side,
                'sort_order' => $participant->sort_order,
            ];
        }

        return [
            'id' => $participant->id,
            'name' => $participant->name,
            'side' => $participant->side,
            'sort_order' => $participant->sort_order,
        ];
    }
}
