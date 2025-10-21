<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Welsh Index of Multiple Deprivation (2019) — ranks by LSOA
        Schema::create('wimd2019', function (Blueprint $table) {
            $table->id();

            // Keep EXACT column names & order as per your sheet
            $table->string('LSOA_code', 9)->nullable();              // e.g. W01000001
            $table->string('LSOA_name', 80)->nullable();
            $table->string('Local_authority_name', 80)->nullable();

            // Overall rank (1 = most deprived … 1909 = least)
            $table->integer('WIMD_2019')->nullable();

            // Domain ranks (integers)
            $table->integer('Income')->nullable();
            $table->integer('Employment')->nullable();
            $table->integer('Health')->nullable();
            $table->integer('Education')->nullable();
            $table->integer('Access_to_services')->nullable();
            $table->integer('Housing')->nullable();
            $table->integer('Community_safety')->nullable();
            $table->integer('Physical_environment')->nullable();

            $table->timestamps();

            // Indexes for fast lookup/sorting
            $table->index('LSOA_code', 'wimd19_lsoa_idx');
            $table->index('WIMD_2019', 'wimd19_overall_idx');
            $table->index(['Local_authority_name', 'WIMD_2019'], 'wimd19_lad_overall_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wimd2019');
    }
};
