<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_costs', function (Blueprint $table) {
            $table->id();
            $table->string('time_period')->nullable();
            $table->string('area_code')->nullable();
            $table->string('area_name')->nullable();
            $table->string('region_or_country_name')->nullable();
            $table->decimal('index', 12, 4)->nullable();
            $table->decimal('monthly_change', 12, 4)->nullable();
            $table->decimal('annual_change', 12, 4)->nullable();
            $table->decimal('rental_price', 12, 4)->nullable();
            $table->decimal('index_one_bed', 12, 4)->nullable();
            $table->decimal('monthly_change_one_bed', 12, 4)->nullable();
            $table->decimal('annual_change_one_bed', 12, 4)->nullable();
            $table->decimal('rental_price_one_bed', 12, 4)->nullable();
            $table->decimal('index_two_bed', 12, 4)->nullable();
            $table->decimal('monthly_change_two_bed', 12, 4)->nullable();
            $table->decimal('annual_change_two_bed', 12, 4)->nullable();
            $table->decimal('rental_price_two_bed', 12, 4)->nullable();
            $table->decimal('index_three_bed', 12, 4)->nullable();
            $table->decimal('monthly_change_three_bed', 12, 4)->nullable();
            $table->decimal('annual_change_three_bed', 12, 4)->nullable();
            $table->decimal('rental_price_three_bed', 12, 4)->nullable();
            $table->decimal('index_four_or_more_bed', 12, 4)->nullable();
            $table->decimal('monthly_change_four_or_more_bed', 12, 4)->nullable();
            $table->decimal('annual_change_four_or_more_bed', 12, 4)->nullable();
            $table->decimal('rental_price_four_or_more_bed', 12, 4)->nullable();
            $table->decimal('index_detached', 12, 4)->nullable();
            $table->decimal('monthly_change_detached', 12, 4)->nullable();
            $table->decimal('annual_change_detached', 12, 4)->nullable();
            $table->decimal('rental_price_detached', 12, 4)->nullable();
            $table->decimal('index_semidetached', 12, 4)->nullable();
            $table->decimal('monthly_change_semidetached', 12, 4)->nullable();
            $table->decimal('annual_change_semidetached', 12, 4)->nullable();
            $table->decimal('rental_price_semidetached', 12, 4)->nullable();
            $table->decimal('index_terraced', 12, 4)->nullable();
            $table->decimal('monthly_change_terraced', 12, 4)->nullable();
            $table->decimal('annual_change_terraced', 12, 4)->nullable();
            $table->decimal('rental_price_terraced', 12, 4)->nullable();
            $table->decimal('index_flat_maisonette', 12, 4)->nullable();
            $table->decimal('monthly_change_flat_maisonette', 12, 4)->nullable();
            $table->decimal('annual_change_flat_maisonette', 12, 4)->nullable();
            $table->decimal('rental_price_flat_maisonette', 12, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_costs');
    }
};
