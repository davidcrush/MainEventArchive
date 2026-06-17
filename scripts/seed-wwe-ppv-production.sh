#!/usr/bin/env bash
# Seed and enrich WWE PPV catalog on production (Laravel Forge).
# Run from the site root after deploying latest code:
#   bash scripts/seed-wwe-ppv-production.sh
#
# Optional dry-run for seed steps only:
#   DRY_RUN=1 bash scripts/seed-wwe-ppv-production.sh
#
# Prerequisites:
#   - php artisan migrate --force
#   - docs/third-party/cagematch/*.mhtml and docs/third-party/netflix/*.mhtml on server (from git)
#   - Back up the production database before first run

set -euo pipefail

cd "$(dirname "$0")/.."

ARTISAN=(php artisan)
DRY_RUN="${DRY_RUN:-0}"
SEED_EXTRA=()
if [[ "$DRY_RUN" == "1" ]]; then
    SEED_EXTRA+=(--dry-run)
    echo "DRY_RUN=1 — seed commands will not write shows."
fi

run_seed() {
    local from=$1
    local to=$2
    echo ""
    echo "========== Seeding WWE PPV ${from}-${to} =========="
    "${ARTISAN[@]}" shows:seed-wwe-ppv-catalog --from="$from" --to="$to" "${SEED_EXTRA[@]}"
}

run_wikipedia() {
    local from=$1
    local to=$2
    local workers=${3:-4}
    echo ""
    echo "========== Wikipedia import WWE ${from}-${to} =========="
    "${ARTISAN[@]}" shows:import wikipedia --promotion=wwe --from="$from" --to="$to" --workers="$workers"
}

run_venues() {
    local from=$1
    local to=$2
    echo ""
    echo "========== Venues import WWE ${from}-${to} =========="
    "${ARTISAN[@]}" shows:import-venues --promotion=wwe --from="$from" --to="$to"
}

run_verify() {
    local from=$1
    local to=$2
    echo ""
    echo "========== Verify Wikipedia WWE ${from}-${to} =========="
    "${ARTISAN[@]}" shows:verify-wikipedia --promotion=wwe --from="$from" --to="$to" || true
}

run_netflix_imports() {
    echo ""
    echo "========== Netflix catalog import =========="
    local count=0
    for f in docs/third-party/netflix/WWE*.mhtml; do
        [[ -f "$f" ]] || continue
        echo "--- $(basename "$f") ---"
        "${ARTISAN[@]}" videos:import-netflix --promotion=wwe --html="$f"
        count=$((count + 1))
    done
    echo "Imported from ${count} Netflix save file(s)."
}

publish_wwe_ppvs() {
    echo ""
    echo "========== Publish WWE PPVs with match cards =========="
    "${ARTISAN[@]}" tinker --execute '
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Show;
use App\Services\BrowseCache;

$exclude = [
    "summerslam-2026",
    "summerslam-2026-aug",
    "night-of-champions-2026",
    "clash-in-italy-2026",
    "money-in-the-bank-2026",
];

$count = Show::query()
    ->whereHas("promotion", fn ($q) => $q->where("slug", "wwe"))
    ->where("show_type", ShowType::Ppv)
    ->where("status", ShowStatus::PendingReview)
    ->whereNotIn("slug", $exclude)
    ->whereHas("matches")
    ->update([
        "status" => ShowStatus::Published,
        "verified_at" => now(),
    ]);

BrowseCache::invalidate();

$pending = Show::query()
    ->whereHas("promotion", fn ($q) => $q->where("slug", "wwe"))
    ->where("show_type", ShowType::Ppv)
    ->where("status", ShowStatus::PendingReview)
    ->count();

echo "Published: {$count}\n";
echo "WWE PPVs still pending review: {$pending}\n";
'
}

echo "WWE PPV production pipeline starting at $(date -Iseconds)"

# Phase 1 — Catalog shells
run_seed 1996 2001
run_seed 2002 2010
run_seed 2011 2020
run_seed 2021 2026

if [[ "$DRY_RUN" == "1" ]]; then
    echo ""
    echo "DRY_RUN complete. Re-run without DRY_RUN=1 to enrich and publish."
    exit 0
fi

# Phase 2 — Wikipedia + venues
run_wikipedia 1996 2001 2
run_venues 1996 2001

run_wikipedia 2002 2010 4
run_venues 2002 2010
run_verify 2002 2010

run_wikipedia 2011 2020 4
run_venues 2011 2020
run_verify 2011 2020

run_wikipedia 2021 2026 4
run_venues 2021 2026
run_verify 2021 2026

# Phase 3 — Netflix video links (browse Video badge + watchable filter)
run_netflix_imports

# Phase 4 — Publish enriched shows (skips future/incomplete shells)
publish_wwe_ppvs

echo ""
echo "WWE PPV production pipeline finished at $(date -Iseconds)"
echo "Check /browse?promotion=wwe and Filament → Shows → WWE for any pending rows."
