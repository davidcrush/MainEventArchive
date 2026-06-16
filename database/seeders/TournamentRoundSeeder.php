<?php

namespace Database\Seeders;

use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Database\Seeder;

class TournamentRoundSeeder extends Seeder
{
    /**
     * Tag tournament_round on matches for known bracket PPVs.
     *
     * Round 1 = opening bouts visible with spoilers off.
     * Round 2+ = participant placeholders when spoilers off.
     *
     * @var array<string, array<int, int>>
     */
    private const ROUNDS_BY_SHOW = [
        'vengeance-2001' => [
            8 => 1, // WWF Championship — Austin vs Angle (unification opening bout)
            9 => 1, // World Championship — Jericho vs Rock (unification opening bout)
            10 => 2, // Undisputed Championship final — Jericho vs Austin
        ],
        'survivor-series-1998' => [
            5 => 1, // Deadly Game QF — Mankind vs Gill
            6 => 1, // Deadly Game QF — Al Snow vs Jarrett
            7 => 1, // Deadly Game QF — Austin vs Big Boss Man
            9 => 1, // Deadly Game QF — Shamrock vs Goldust
            11 => 1, // Deadly Game QF — Kane vs Undertaker
            12 => 2, // Deadly Game SF — Mankind vs Al Snow
            13 => 2, // Deadly Game SF — Rock vs Shamrock
            18 => 3, // Deadly Game final — Rock vs Mankind (WWF Championship)
        ],
        'king-of-the-ring-1996' => [
            2 => 1, // QF — Austin vs Mero
            3 => 1, // QF — Roberts vs Vader
            5 => 1, // QF — Warrior vs Lawler
            8 => 3, // Final — Austin vs Roberts
        ],
        'king-of-the-ring-1997' => [
            2 => 1, // QF — Helmsley vs Ahmed
            3 => 1, // QF — Mankind vs Lawler
            4 => 1, // QF — Goldust vs Crush
            6 => 2, // SF — Helmsley vs Mankind
        ],
        'king-of-the-ring-1998' => [
            2 => 1, // QF — Shamrock vs Jarrett
            3 => 1, // QF — Rock vs Severn
            5 => 1, // QF — X-Pac vs Owen Hart
            7 => 3, // Final — Shamrock vs Rock
        ],
        'king-of-the-ring-1999' => [
            6 => 1, // QF — X-Pac vs Hardcore Holly
            7 => 1, // QF — Kane vs Big Show
            8 => 1, // QF — Billy Gunn vs Shamrock
            9 => 1, // QF — Road Dogg vs Chyna
            11 => 2, // SF — Billy Gunn vs Kane
            12 => 2, // SF — X-Pac vs Road Dogg
            14 => 3, // Final — Billy Gunn vs X-Pac
        ],
        'king-of-the-ring-2000' => [
            2 => 1, // QF — Austin vs Mero
            3 => 1, // QF — Roberts vs Vader
            5 => 1, // QF — Warrior vs Lawler
            8 => 3, // Final — Austin vs Roberts
        ],
        'king-of-the-ring-2001' => [
            2 => 1, // QF — Angle vs Christian
            3 => 1, // QF — Edge vs Rhyno
            5 => 3, // Final — Edge vs Angle
        ],
    ];

    public function run(): void
    {
        foreach (self::ROUNDS_BY_SHOW as $slug => $roundsByCardOrder) {
            $show = Show::query()->where('slug', $slug)->first();

            if ($show === null) {
                $this->command?->warn("Show not found: {$slug}");

                continue;
            }

            WrestlingMatch::query()
                ->where('show_id', $show->id)
                ->update(['tournament_round' => null]);

            foreach ($roundsByCardOrder as $cardOrder => $round) {
                $updated = WrestlingMatch::query()
                    ->where('show_id', $show->id)
                    ->where('card_order', $cardOrder)
                    ->update(['tournament_round' => $round]);

                if ($updated === 0) {
                    $this->command?->warn("No match at {$slug} card #{$cardOrder}");
                }
            }

            $this->command?->info("Tagged tournament rounds for {$show->title}");
        }
    }
}
