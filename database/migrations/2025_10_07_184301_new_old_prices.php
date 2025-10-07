<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('new_old_prices', function (Blueprint $table) {
            $table->id();

            // === Columns directly mirroring the CSV (snake_case) ===
            // CSV "Date"
            $table->date('date')->index(); // e.g., 2025-04-01

            // CSV "Region_Name", "Area_Code"
            $table->string('region_name', 128)->index();
            $table->string('area_code', 16)->index();

            // CSV New build metrics
            $table->decimal('new_build_average_price', 12, 2)->nullable();
            $table->decimal('new_build_index', 10, 4)->nullable();
            $table->decimal('new_build_monthly_change', 7, 4)->nullable();
            $table->decimal('new_build_annual_change', 7, 4)->nullable();
            $table->unsignedInteger('new_build_sales_volume')->nullable();

            // CSV Existing property metrics
            $table->decimal('existing_property_average_price', 12, 2)->nullable();
            $table->decimal('existing_property_index', 10, 4)->nullable();
            $table->decimal('existing_property_monthly_change', 7, 4)->nullable();
            $table->decimal('existing_property_annual_change', 7, 4)->nullable();
            $table->unsignedInteger('existing_property_sales_volume')->nullable();

            // === Auto-updating generated helpers (no import work needed) ===
            // Country prefix from area_code (E/W/S/N/K/…)
            $table->char('country_prefix', 1)->storedAs('LEFT(area_code, 1)')->index();

            // Human-friendly country name from prefix
            // Note: tweak labels if you prefer different wording for 'K'
            $table->string('country_name', 32)->storedAs("
                CASE LEFT(area_code, 1)
                  WHEN 'E' THEN 'England'
                  WHEN 'W' THEN 'Wales'
                  WHEN 'S' THEN 'Scotland'
                  WHEN 'N' THEN 'Northern Ireland'
                  WHEN 'K' THEN 'Aggregate'
                  ELSE 'Other'
                END
            ");

            // Flags aggregate rows (K…)
            $table->boolean('is_aggregate')->storedAs("(LEFT(area_code, 1) = 'K')")->index();

            // Useful composite index for point-in-time lookups
            $table->index(['date', 'area_code']);
            // If you’re confident there’s only one row per (date, area_code), uncomment:
            // $table->unique(['date', 'area_code']);

            // Optional: created_at/updated_at if you want them (not required for imports)
            // $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('new_old_prices');
    }
};
