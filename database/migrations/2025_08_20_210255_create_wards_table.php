<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wards', function (Blueprint $table) {
            $table->id();

            // Ward identifiers
            $table->string('wd25cd', 9)->unique();      // Ward GSS code
            $table->string('wd25nm', 100);              // Ward name (EN)
            $table->string('wd25nmw', 100)->nullable(); // Ward name (Welsh)

            // Parent LAD
            $table->string('lad25cd', 9)->index();
            $table->string('lad25nm', 100);
            $table->string('lad25nmw', 100)->nullable();

            // Geometry attributes (from BSC export)
            $table->integer('bng_e')->nullable();  // British National Grid Easting
            $table->integer('bng_n')->nullable();  // British National Grid Northing
            $table->decimal('long', 10, 7)->nullable();
            $table->decimal('lat', 10, 7)->nullable();

            // Extra boundary metadata (common in ONS shapefile exports)
            $table->string('globalid', 50)->nullable();
            $table->double('shape__area')->nullable();
            $table->double('shape__length')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
