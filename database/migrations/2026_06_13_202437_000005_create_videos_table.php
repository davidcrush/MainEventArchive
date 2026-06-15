<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('matches')->cascadeOnDelete();
            $table->string('provider');
            $table->string('external_id');
            $table->string('url');
            $table->string('title')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('embeddable')->default(true);
            $table->string('embed_disabled_reason')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        DB::statement('
            ALTER TABLE videos ADD CONSTRAINT videos_show_or_match_check
            CHECK (
                (show_id IS NOT NULL AND match_id IS NULL)
                OR (show_id IS NULL AND match_id IS NOT NULL)
            )
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
