<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('england_council_housing_stock', function (Blueprint $table) {
            $table->id();

            // Authority identifiers
            $table->string('local_authority');
            $table->string('local_authority_code', 9);

            // Current LAD (2023) mapping
            $table->string('lad23_name');
            $table->string('lad23_code', 9);
            $table->string('lad23_type', 5);

            // Geography
            $table->string('region_name');
            $table->string('region_code', 9)->nullable();

            $table->string('county_name')->nullable();
            $table->string('county_code', 9)->nullable();

            // Time & status
            $table->string('year', 9); // e.g. 1978-79
            $table->string('status');

            // Stock figures
            $table->unsignedInteger('total_stock')->nullable();
            $table->unsignedInteger('new_builds')->nullable();
            $table->unsignedInteger('acquisitions')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['lad23_code', 'year']);
            $table->index(['local_authority_code']);
            $table->index(['region_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('england_council_housing_stock');
    }
};
