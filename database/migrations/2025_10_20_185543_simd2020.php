<?php
// database/migrations/2025_10_19_221500_create_simd2020_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('simd2020', function (Blueprint $table) {
            $table->id();

            // Indexable Data_Zone
            $table->string('Data_Zone', 12)->nullable();

            $table->text('Intermediate_Zone')->nullable();
            $table->text('Council_area')->nullable();
            $table->text('Total_population')->nullable();
            $table->text('Working_Age_population')->nullable();
            $table->text('SIMD2020v2_Rank')->nullable();
            $table->text('SIMD_2020v2_Percentile')->nullable();
            $table->text('SIMD2020v2_Vigintile')->nullable();
            $table->text('SIMD2020v2_Decile')->nullable();
            $table->text('SIMD2020v2_Quintile')->nullable();
            $table->text('SIMD2020v2_Income_Domain_Rank')->nullable();
            $table->text('SIMD2020_Employment_Domain_Rank')->nullable();
            $table->text('SIMD2020_Health_Domain_Rank')->nullable();
            $table->text('SIMD2020_Education_Domain_Rank')->nullable();
            $table->text('SIMD2020_Access_Domain_Rank')->nullable();
            $table->text('SIMD2020_Crime_Domain_Rank')->nullable();
            $table->text('SIMD2020_Housing_Domain_Rank')->nullable();
            $table->text('income_rate')->nullable();
            $table->text('income_count')->nullable();
            $table->text('employment_rate')->nullable();
            $table->text('employment_count')->nullable();
            $table->text('CIF')->nullable();
            $table->text('ALCOHOL')->nullable();
            $table->text('DRUG')->nullable();
            $table->text('SMR')->nullable();
            $table->text('DEPRESS')->nullable();
            $table->text('LBWT')->nullable();
            $table->text('EMERG')->nullable();
            $table->text('Attendance')->nullable();
            $table->text('Attainment')->nullable();
            $table->text('no_qualifications')->nullable();
            $table->text('not_participating')->nullable();
            $table->text('University')->nullable();
            $table->text('crime_count')->nullable();
            $table->text('crime_rate')->nullable();
            $table->text('overcrowded_count')->nullable();
            $table->text('nocentralheating_count')->nullable();
            $table->text('overcrowded_rate')->nullable();
            $table->text('nocentralheating_rate')->nullable();
            $table->text('drive_petrol')->nullable();
            $table->text('drive_GP')->nullable();
            $table->text('drive_post')->nullable();
            $table->text('drive_primary')->nullable();
            $table->text('drive_retail')->nullable();
            $table->text('drive_secondary')->nullable();
            $table->text('PT_GP')->nullable();
            $table->text('PT_post')->nullable();
            $table->text('PT_retail')->nullable();
            $table->text('broadband')->nullable();

            $table->timestamps();

            $table->index('Data_Zone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simd2020');
    }
};
