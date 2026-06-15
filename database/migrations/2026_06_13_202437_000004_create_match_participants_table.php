<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('side');
            $table->boolean('is_surprise_entrant')->default(false);
            $table->string('placeholder_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE match_participants ADD COLUMN name_search tsvector');
        DB::statement('CREATE INDEX match_participants_name_search_idx ON match_participants USING GIN (name_search)');
        DB::statement("
            CREATE OR REPLACE FUNCTION match_participants_name_search_trigger() RETURNS trigger AS $$
            begin
                new.name_search := to_tsvector('english', coalesce(new.name, ''));
                return new;
            end
            $$ LANGUAGE plpgsql
        ");
        DB::statement('
            CREATE TRIGGER match_participants_name_search_update
            BEFORE INSERT OR UPDATE ON match_participants
            FOR EACH ROW EXECUTE FUNCTION match_participants_name_search_trigger()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS match_participants_name_search_update ON match_participants');
        DB::statement('DROP FUNCTION IF EXISTS match_participants_name_search_trigger()');
        Schema::dropIfExists('match_participants');
    }
};
