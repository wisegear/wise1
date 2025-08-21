<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onspd', function (Blueprint $table) {
            $table->id();

            // Postcode identifiers
            $table->string('pcd', 10)->nullable();
            $table->string('pcd2', 10)->nullable();
            $table->string('pcds', 10)->nullable()->unique(); // join to PPD postcodes

            // Lifecycle (ONSPD uses YYYYMM)
            $table->string('dointr', 6)->nullable()->index();
            $table->string('doterm', 6)->nullable()->index();

            // Older ONS admin codes (county, district, ward, parish, etc.)
            $table->string('oscty', 12)->nullable()->index();
            $table->string('ced', 12)->nullable()->index();
            $table->string('oslaua', 12)->nullable()->index();
            $table->string('osward', 12)->nullable()->index();
            $table->string('parish', 12)->nullable()->index();

            // Classification and OS grid
            $table->char('usertype', 1)->nullable();           // S/L
            $table->integer('oseast1m')->nullable();
            $table->integer('osnrth1m')->nullable();
            $table->tinyInteger('osgrdind')->nullable();

            // Health / NHS-style codes (varies by vintage)
            $table->string('oshlthau', 12)->nullable()->index();
            $table->string('nhser', 12)->nullable()->index();

            // Mid/legacy geography codes present in your header
            $table->string('ctry', 12)->nullable()->index();
            $table->string('rgn', 12)->nullable()->index();
            $table->string('streg', 12)->nullable()->index();
            $table->string('pcon', 12)->nullable()->index();
            $table->string('eer', 12)->nullable()->index();
            $table->string('teclec', 12)->nullable()->index();
            $table->string('ttwa', 12)->nullable()->index();
            $table->string('pct', 12)->nullable()->index();
            $table->string('itl', 12)->nullable()->index();
            $table->string('statsward', 12)->nullable()->index();
            $table->string('oa01', 12)->nullable()->index();
            $table->string('casward', 12)->nullable()->index();
            $table->string('npark', 12)->nullable()->index();
            $table->string('lsoa01', 12)->nullable()->index();
            $table->string('msoa01', 12)->nullable()->index();
            $table->string('ur01ind', 12)->nullable()->index();
            $table->string('oac01', 12)->nullable()->index();
            $table->string('oa11', 12)->nullable()->index();
            $table->string('lsoa11', 12)->nullable()->index();
            $table->string('msoa11', 12)->nullable()->index();
            $table->string('wz11', 12)->nullable()->index();
            $table->string('sicbl', 12)->nullable()->index();
            $table->string('bua24', 12)->nullable()->index();
            $table->string('ru11ind', 12)->nullable()->index();
            $table->string('oac11', 12)->nullable()->index();

            // Coordinates
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('long', 9, 6)->nullable();

            // Partnerships / police / deprivation index
            $table->string('lep1', 12)->nullable()->index();
            $table->string('lep2', 12)->nullable()->index();
            $table->string('pfa', 12)->nullable()->index();
            $table->integer('imd')->nullable();

            // Newer health / admin
            $table->string('calncv', 12)->nullable()->index();
            $table->string('icb', 12)->nullable()->index();

            // Current statistical geographies (2021/2025)
            $table->string('oa21', 12)->nullable()->index();
            $table->string('lsoa21', 12)->nullable()->index();
            $table->string('msoa21', 12)->nullable()->index();
            $table->string('ruc21', 12)->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onspd');
    }
};
