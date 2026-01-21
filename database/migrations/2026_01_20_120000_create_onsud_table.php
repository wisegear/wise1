<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onsud', function (Blueprint $table) {
            $table->bigIncrements('row_id');

            $table->string('UPRN', 12)->nullable()->index();
            $table->integer('GRIDGB1E')->nullable();
            $table->integer('GRIDGB1N')->nullable();
            $table->string('PCDS', 10)->nullable()->index();
            $table->string('CTY25CD', 12)->nullable();
            $table->string('CED25CD', 12)->nullable();
            $table->string('LAD25CD', 12)->nullable();
            $table->string('WD25CD', 12)->nullable();
            $table->string('PARNCP25CD', 12)->nullable();
            $table->string('HLTH19CD', 12)->nullable();
            $table->string('ctry25cd', 12)->nullable();
            $table->string('RGN25CD', 12)->nullable();
            $table->string('PCON24CD', 12)->nullable();
            $table->string('EER20CD', 12)->nullable();
            $table->string('ttwa15cd', 12)->nullable();
            $table->string('itl25cd', 12)->nullable();
            $table->string('NPARK16CD', 12)->nullable();
            $table->string('OA21CD', 12)->nullable();
            $table->string('lsoa21cd', 12)->nullable();
            $table->string('msoa21cd', 12)->nullable();
            $table->string('WZ11CD', 12)->nullable();
            $table->string('SICBL24CD', 12)->nullable();
            $table->string('BUA24CD', 12)->nullable();
            $table->string('BUASD11CD', 12)->nullable();
            $table->string('ruc21ind', 12)->nullable();
            $table->string('oac21ind', 12)->nullable();
            $table->string('lep21cd1', 12)->nullable();
            $table->string('lep21cd2', 12)->nullable();
            $table->string('pfa23cd', 12)->nullable();
            $table->integer('imd19ind')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onsud');
    }
};
