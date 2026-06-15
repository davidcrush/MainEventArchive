<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->unsignedSmallInteger('episode_number')->nullable()->after('date');
            $table->decimal('tv_rating', 3, 1)->nullable()->after('attendance');
        });
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropColumn(['episode_number', 'tv_rating']);
        });
    }
};
