<?php

namespace App\Importers\Concerns;

use App\Data\ParsedWikipediaMatch;
use App\Models\MatchParticipant;
use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Support\Facades\DB;

/**
 * Replace-all persistence of parsed matches for a show. Shared by importers that
 * rebuild a show's card from a single authoritative source (Wikipedia, Fandom).
 */
trait PersistsParsedMatches
{
    /**
     * @param  list<ParsedWikipediaMatch>  $parsedMatches
     */
    private function persistMatches(Show $show, array $parsedMatches): int
    {
        return DB::transaction(function () use ($show, $parsedMatches): int {
            $show->matches()->each(function (WrestlingMatch $match): void {
                $match->participants()->delete();
                $match->delete();
            });

            foreach ($parsedMatches as $parsedMatch) {
                $match = WrestlingMatch::query()->create([
                    'show_id' => $show->id,
                    'card_order' => $parsedMatch->cardOrder,
                    'match_type' => $parsedMatch->matchType,
                    'title_name' => $parsedMatch->titleName,
                    'entrant_names' => $parsedMatch->entrantNames !== [] ? $parsedMatch->entrantNames : null,
                    'is_rateable' => $parsedMatch->isRateable,
                    'is_ppv' => $parsedMatch->isPpv,
                    'winner_side' => $parsedMatch->winnerSide,
                    'finish' => $parsedMatch->finish,
                    'duration_seconds' => $parsedMatch->durationSeconds,
                ]);

                foreach ($parsedMatch->participants as $participant) {
                    MatchParticipant::query()->create([
                        'match_id' => $match->id,
                        'name' => $participant['name'],
                        'side' => $participant['side'],
                        'sort_order' => $participant['sort_order'],
                    ]);
                }
            }

            return count($parsedMatches);
        });
    }
}
