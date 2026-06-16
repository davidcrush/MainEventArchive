<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('slug');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('logo_path');
            $table->unsignedSmallInteger('active_from_year')->nullable()->after('founded_year');
            $table->unsignedSmallInteger('active_to_year')->nullable()->after('active_from_year');
            $table->boolean('is_active')->default(false)->after('active_to_year');
            $table->string('headquarters')->nullable()->after('is_active');
            $table->text('description')->nullable()->after('headquarters');
            $table->string('wikipedia_url')->nullable()->after('description');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('wikipedia_url');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'founded_year',
                'active_from_year',
                'active_to_year',
                'is_active',
                'headquarters',
                'description',
                'wikipedia_url',
                'sort_order',
            ]);
        });
    }
};
