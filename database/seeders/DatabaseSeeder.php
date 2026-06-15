<?php

namespace Database\Seeders;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        $wcw = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $wcw->id,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997',
            'date' => '1997-12-28',
            'venue' => 'MCI Center',
            'city' => 'Washington, D.C.',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);
    }
}
