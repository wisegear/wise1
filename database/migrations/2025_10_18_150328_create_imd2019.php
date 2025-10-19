<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imd2019', function (Blueprint $table) {
            $table->id();

            // Original fields (safe identifiers)
            $table->string('FeatureCode', 20);              // LSOA code, e.g. E01005278
            $table->string('DateCode', 10)->nullable();     // e.g. 2019
            $table->string('Measurement', 50)->nullable();  // e.g. Rank / Decile
            $table->string('Units', 50)->nullable();        // often empty / descriptive
            $table->integer('Value')->nullable();           // numeric value
            // CSV header often reads "Indices of Deprivation"; we store as an underscore identifier
            $table->string('Indices_of_Deprivation', 150)->nullable();

            $table->timestamps();

            // Indexes for fast lookups & joins
            $table->index('FeatureCode', 'idx_imd_featurecode'); // join on LSOA
            $table->index('Measurement', 'idx_imd_measurement');
            $table->index('Indices_of_Deprivation', 'idx_imd_domain');

            // Covering composite index for common query: LSOA + Measurement + Domain
            $table->index(
                ['FeatureCode', 'Measurement', 'Indices_of_Deprivation'],
                'idx_imd_lsoa_measurement_domain'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imd2019');
    }
};
