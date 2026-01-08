<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scottish_housing_stock', function (Blueprint $table) {
            $table->id();

            // One row per council per year
            $table->unsignedSmallInteger('year'); // 2000+
            $table->string('council', 120);

            // Totals
            $table->unsignedInteger('total_stock')->nullable();

            // Property type breakdown
            $table->unsignedInteger('house')->nullable();
            $table->unsignedInteger('all_flats')->nullable();
            $table->unsignedInteger('high_rise_flat')->nullable();
            $table->unsignedInteger('tenement')->nullable();
            $table->unsignedInteger('four_in_a_block')->nullable();
            $table->unsignedInteger('other_flat')->nullable();
            $table->unsignedInteger('property_type_unknown')->nullable(); // "Unknown" (property type)

            // Build period breakdown
            $table->unsignedInteger('pre_1919')->nullable();
            $table->unsignedInteger('y1919_44')->nullable();
            $table->unsignedInteger('y1945_64')->nullable();
            $table->unsignedInteger('y1965_1982')->nullable();
            $table->unsignedInteger('post_1982')->nullable();
            $table->unsignedInteger('build_period_unknown')->nullable(); // "Unknown" (build period)

            // Optional, but handy
            $table->timestamps();

            $table->unique(['year', 'council']);
            $table->index('year');
            $table->index('council');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scottish_housing_stock');
    }
};
