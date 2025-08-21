<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_authority_district', function (Blueprint $table) {
            $table->id();

            // Core codes + names
            $table->string('lad25cd', 9)->unique();      // LAD code (GSS)
            $table->string('lad25nm', 100);              // LAD name (EN)
            $table->string('lad25nmw', 100)->nullable(); // LAD name (Welsh)

            // Geometry attributes (from BSC boundary CSV)
            $table->integer('bng_e')->nullable();   // British National Grid Easting
            $table->integer('bng_n')->nullable();   // British National Grid Northing
            $table->decimal('long', 10, 7)->nullable();
            $table->decimal('lat', 10, 7)->nullable();

            // Extra columns usually present in BSC exports
            $table->string('globalid', 50)->nullable();
            $table->double('shape__area')->nullable();
            $table->double('shape__length')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_authority_district');
    }
};
