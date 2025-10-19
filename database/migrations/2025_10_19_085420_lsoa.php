<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lsoa21_ruc_geo', function (Blueprint $t) {
            // Columns in the same order as your CSV
            $t->unsignedInteger('FID')->nullable();       // Row ID from source
            $t->char('LSOA21CD', 9)->primary();           // E01000001
            $t->string('LSOA21NM', 60);                   // City of London 001A
            $t->string('LSOA21NMW', 60)->nullable();      // Welsh name
            $t->integer('BNG_E')->nullable();             // Easting
            $t->integer('BNG_N')->nullable();             // Northing
            $t->decimal('LAT', 9, 6)->nullable();         // Latitude
            $t->decimal('LONG', 9, 6)->nullable();        // Longitude
            $t->string('GlobalID', 64)->nullable();       // Unique ID
            $t->char('LSOA21CID', 9)->nullable();         // From your file (looks like duplicate ID, include it)
            $t->string('RUC21CD', 8)->nullable();         // UN1 etc.
            $t->string('RUC21NM', 80)->nullable();        // Urban: Nearer to a major town or city
            $t->string('Urban_rura', 40)->nullable();     // Urban / Rural
            $t->double('Shape_Leng')->nullable();         // Shape length
            $t->double('Shape_Area')->nullable();         // Shape area
            $t->double('Shape_Length')->nullable();       // Shape length (duplicate in some exports)
            $t->timestamps();

            // Helpful indexes
            $t->index('LSOA21NM');
            $t->index('RUC21CD');
            $t->index('RUC21NM');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lsoa21_ruc_geo');
    }
};
