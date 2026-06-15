<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->date('date');
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('show_type');
            $table->string('brand')->nullable();
            $table->unsignedInteger('attendance')->nullable();
            $table->string('status')->default('draft');
            $table->string('cagematch_url')->nullable();
            $table->string('source')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['promotion_id', 'date']);
            $table->index('status');
            $table->index('date');
        });

        DB::statement('ALTER TABLE shows ADD COLUMN title_search tsvector');
        DB::statement('CREATE INDEX shows_title_search_idx ON shows USING GIN (title_search)');
        DB::statement("
            CREATE OR REPLACE FUNCTION shows_title_search_trigger() RETURNS trigger AS $$
            begin
                new.title_search := to_tsvector('english', coalesce(new.title, ''));
                return new;
            end
            $$ LANGUAGE plpgsql
        ");
        DB::statement('
            CREATE TRIGGER shows_title_search_update
            BEFORE INSERT OR UPDATE ON shows
            FOR EACH ROW EXECUTE FUNCTION shows_title_search_trigger()
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS shows_title_search_update ON shows');
        DB::statement('DROP FUNCTION IF EXISTS shows_title_search_trigger()');
        Schema::dropIfExists('shows');
    }
};
