<?php
// database/migrations/2025_08_16_000000_create_repo_la_quarterlies_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Create the table used to store the LA CSV rows
    public function up(): void
    {
        Schema::create('repo_la_quarterlies', function (Blueprint $table) {
            $table->id();

            // Period
            $table->unsignedSmallInteger('year');                // e.g. 2003..2025
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);   // matches CSV exactly

            // Dimensions (match CSV headers for easy import)
            $table->string('possession_type', 64);               // e.g. Mortgage / Private Landlord / Social Landlord / Accelerated_Landlord
            $table->string('possession_action', 32);             // e.g. Claims / Orders / Warrants / Repossessions
            $table->string('la_code', 12);                       // e.g. E06000001
            $table->string('local_authority', 128);              // e.g. Hartlepool
            $table->string('county_ua', 128);                    // county or unitary authority label (may end with " UA")
            $table->string('region', 64);                        // e.g. North East

            // Measure
            $table->unsignedInteger('value');                    // number of cases

            $table->timestamps();

            // Indexes for fast filtering/rollups
            $table->index(['year', 'quarter', 'possession_type', 'possession_action'], 'idx_period_type_action');
            $table->index(['county_ua'], 'idx_county');
            $table->index(['la_code'], 'idx_la_code');
            $table->index(['region'], 'idx_region');

            // Optional: if you want to prevent duplicate rows being inserted later,
            // you *can* enforce uniqueness at this grain. Commented out to avoid
            // surprises during first import.
            // $table->unique(['year','quarter','possession_type','possession_action','la_code'], 'uniq_row_grain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repo_la_quarterlies');
    }
};
