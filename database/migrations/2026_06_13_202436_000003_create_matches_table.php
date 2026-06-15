<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('card_order');
            $table->string('match_type');
            $table->string('title_name')->nullable();
            $table->boolean('is_surprise')->default(false);
            $table->boolean('is_rateable')->default(true);
            $table->unsignedTinyInteger('winner_side')->nullable();
            $table->string('finish')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('timestamp_start')->nullable();
            $table->unsignedInteger('timestamp_end')->nullable();
            $table->boolean('title_changed')->default(false);
            $table->timestamps();

            $table->unique(['show_id', 'card_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
