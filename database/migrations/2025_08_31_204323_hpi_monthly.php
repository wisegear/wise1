<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hpi_monthly', function (Blueprint $table) {
            // Natural key
            $table->string('AreaCode', 12);
            $table->date('Date');

            // Headline
            $table->string('RegionName', 100)->index();
            $table->decimal('AveragePrice', 12, 2)->nullable();
            $table->decimal('Index', 10, 3)->nullable();
            $table->decimal('IndexSA', 10, 3)->nullable();
            $table->decimal('1m%Change', 7, 4)->nullable();
            $table->decimal('12m%Change', 7, 4)->nullable();
            $table->decimal('AveragePriceSA', 12, 2)->nullable();
            $table->unsignedInteger('SalesVolume')->nullable();

            // Detached
            $table->decimal('DetachedPrice', 12, 2)->nullable();
            $table->decimal('DetachedIndex', 10, 3)->nullable();
            $table->decimal('Detached1m%Change', 7, 4)->nullable();
            $table->decimal('Detached12m%Change', 7, 4)->nullable();

            // Semi-detached
            $table->decimal('SemiDetachedPrice', 12, 2)->nullable();
            $table->decimal('SemiDetachedIndex', 10, 3)->nullable();
            $table->decimal('SemiDetached1m%Change', 7, 4)->nullable();
            $table->decimal('SemiDetached12m%Change', 7, 4)->nullable();

            // Terraced
            $table->decimal('TerracedPrice', 12, 2)->nullable();
            $table->decimal('TerracedIndex', 10, 3)->nullable();
            $table->decimal('Terraced1m%Change', 7, 4)->nullable();
            $table->decimal('Terraced12m%Change', 7, 4)->nullable();

            // Flat
            $table->decimal('FlatPrice', 12, 2)->nullable();
            $table->decimal('FlatIndex', 10, 3)->nullable();
            $table->decimal('Flat1m%Change', 7, 4)->nullable();
            $table->decimal('Flat12m%Change', 7, 4)->nullable();

            // Funding: Cash / Mortgage
            $table->decimal('CashPrice', 12, 2)->nullable();
            $table->decimal('CashIndex', 10, 3)->nullable();
            $table->decimal('Cash1m%Change', 7, 4)->nullable();
            $table->decimal('Cash12m%Change', 7, 4)->nullable();
            $table->unsignedInteger('CashSalesVolume')->nullable();

            $table->decimal('MortgagePrice', 12, 2)->nullable();
            $table->decimal('MortgageIndex', 10, 3)->nullable();
            $table->decimal('Mortgage1m%Change', 7, 4)->nullable();
            $table->decimal('Mortgage12m%Change', 7, 4)->nullable();
            $table->unsignedInteger('MortgageSalesVolume')->nullable();

            // Buyer type: FTB / FOO
            $table->decimal('FTBPrice', 12, 2)->nullable();
            $table->decimal('FTBIndex', 10, 3)->nullable();
            $table->decimal('FTB1m%Change', 7, 4)->nullable();
            $table->decimal('FTB12m%Change', 7, 4)->nullable();

            $table->decimal('FOOPrice', 12, 2)->nullable();
            $table->decimal('FOOIndex', 10, 3)->nullable();
            $table->decimal('FOO1m%Change', 7, 4)->nullable();
            $table->decimal('FOO12m%Change', 7, 4)->nullable();

            // New / Old (with volumes)
            $table->decimal('NewPrice', 12, 2)->nullable();
            $table->decimal('NewIndex', 10, 3)->nullable();
            $table->decimal('New1m%Change', 7, 4)->nullable();
            $table->decimal('New12m%Change', 7, 4)->nullable();
            $table->unsignedInteger('NewSalesVolume')->nullable();

            $table->decimal('OldPrice', 12, 2)->nullable();
            $table->decimal('OldIndex', 10, 3)->nullable();
            $table->decimal('Old1m%Change', 7, 4)->nullable();
            $table->decimal('Old12m%Change', 7, 4)->nullable();
            $table->unsignedInteger('OldSalesVolume')->nullable();

            $table->primary(['AreaCode', 'Date']);
            $table->index(['Date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hpi_monthly');
    }
};
