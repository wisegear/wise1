<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ni_deprivation', function (Blueprint $table) {

            // Council / geography info
            $table->string('LGD2014dcode', 20)->nullable();
            $table->string('LGD2014name', 100)->nullable();
            $table->string('UR2015', 20)->nullable(); // e.g. Rural / Urban

            // Primary identifier for the Small Area (e.g. N00000001)
            $table->string('SA2011', 20)->primary();

            $table->string('SOA2001name', 100)->nullable();

            // Overall deprivation
            $table->unsignedInteger('MDM_rank')->nullable();

            // Domain ranks & percentages
            $table->unsignedInteger('D1_Income_rank')->nullable();
            $table->unsignedInteger('Income_perc')->nullable();

            $table->unsignedInteger('D2_Empl_rank')->nullable();
            $table->unsignedInteger('Empl_perc')->nullable();

            $table->unsignedInteger('D3_Health_rank')->nullable();

            $table->unsignedInteger('P4_Education_rank')->nullable();

            $table->unsignedInteger('P5_Access_rank')->nullable();

            $table->unsignedInteger('D6_LivEnv_rank')->nullable();

            $table->unsignedInteger('D7_CD_rank')->nullable();

            // standard created_at / updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ni_deprivation');
    }
};
