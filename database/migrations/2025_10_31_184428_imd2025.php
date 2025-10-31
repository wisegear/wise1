<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imd2025', function (Blueprint $table) {
            $table->id();
            
            $table->string('LSOA_Code_2021', 12)->index();
            $table->string('LSOA_Name_2021')->nullable();
            $table->string('LAD_2024', 12)->nullable();
            $table->string('LAD_Name_2024')->nullable();

            $table->integer('Index_of_Multiple_Deprivation_Rank')->nullable();
            $table->integer('Index_of_Multiple_Deprivation_Decile')->nullable();

            $table->integer('Income_Rank')->nullable();
            $table->integer('Income_Decile')->nullable();
            $table->integer('Employment_Rank')->nullable();
            $table->integer('Employment_Decile')->nullable();
            $table->integer('Education_Skills_Training_Rank')->nullable();
            $table->integer('Education_Skills_Training_Decile')->nullable();
            $table->integer('Health_Deprivation_Disability_Rank')->nullable();
            $table->integer('Health_Deprivation_Disability_Decile')->nullable();
            $table->integer('Crime_Rank')->nullable();
            $table->integer('Crime_Decile')->nullable();
            $table->integer('Barriers_Housing_Services_Rank')->nullable();
            $table->integer('Barriers_Housing_Services_Decile')->nullable();
            $table->integer('Living_Environment_Rank')->nullable();
            $table->integer('Living_Environment_Decile')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imd2025');
    }
};
