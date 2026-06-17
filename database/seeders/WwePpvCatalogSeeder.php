<?php

namespace Database\Seeders;

use App\Importers\WwePpvCatalogImporter;
use Illuminate\Database\Seeder;

class WwePpvCatalogSeeder extends Seeder
{
    private const FROM_YEAR = 1996;

    private const TO_YEAR = 2001;

    public function run(): void
    {
        $importer = app(WwePpvCatalogImporter::class);
        $promotion = $importer->ensurePromotion();

        $importer->import($promotion, self::FROM_YEAR, self::TO_YEAR);
    }
}
