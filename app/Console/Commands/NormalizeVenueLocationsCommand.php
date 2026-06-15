<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\VenueLocationNormalizer;
use Illuminate\Console\Command;

class NormalizeVenueLocationsCommand extends Command
{
    protected $signature = 'venues:normalize-locations {--dry-run : Preview changes without saving}';

    protected $description = 'Normalize venue city, state/province, and country fields';

    public function handle(VenueLocationNormalizer $normalizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        Venue::query()
            ->orderBy('name')
            ->each(function (Venue $venue) use ($normalizer, $dryRun, &$updated): void {
                [$city, $stateProvince, $country] = $normalizer->normalize(
                    $venue->city,
                    $venue->state_province,
                    $venue->country,
                );

                $changes = array_filter([
                    'city' => $city !== $venue->city ? [$venue->city, $city] : null,
                    'state_province' => $stateProvince !== $venue->state_province ? [$venue->state_province, $stateProvince] : null,
                    'country' => $country !== $venue->country ? [$venue->country, $country] : null,
                ]);

                if ($changes === []) {
                    return;
                }

                $updated++;

                $this->line("{$venue->name} ({$venue->slug})");

                foreach ($changes as $field => [$before, $after]) {
                    $this->line("  {$field}: ".($before ?? 'null').' → '.($after ?? 'null'));
                }

                if (! $dryRun) {
                    $venue->update([
                        'city' => $city,
                        'state_province' => $stateProvince,
                        'country' => $country,
                    ]);
                }
            });

        $this->info($dryRun
            ? "Dry run complete. {$updated} venue(s) would be updated."
            : "Normalized {$updated} venue(s).");

        return self::SUCCESS;
    }
}
