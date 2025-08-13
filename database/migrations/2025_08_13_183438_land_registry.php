<?php 

// database/migrations/2025_08_13_182215_land_registry.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('land_registry')) {
            // Table already exists (imported externally) — do nothing.
            return;
        }

        Schema::create('land_registry', function (Blueprint $table) {
            $table->char('TransactionID', 36)->nullable();
            $table->unsignedInteger('Price')->nullable();
            $table->dateTime('Date')->nullable();
            $table->string('Postcode', 10)->nullable();
            $table->enum('PropertyType', ['D','S','T','F','O'])->nullable();
            $table->enum('NewBuild', ['Y','N'])->nullable();
            $table->enum('Duration', ['F','L'])->nullable();
            $table->string('PAON', 100)->nullable();
            $table->string('SAON', 100)->nullable();
            $table->string('Street', 100)->nullable();
            $table->string('Locality', 100)->nullable();
            $table->string('TownCity', 100)->nullable();
            $table->string('District', 100)->nullable();
            $table->string('County', 100)->nullable();
            $table->enum('PPDCategoryType', ['A','B'])->nullable();
            $table->char('RecordStatus', 1)->nullable();

            $table->index(['Postcode','Date'], 'idx_postcode_date');
        });
    }

    public function down(): void
    {
        // Only drop if Laravel created it. If your live DB owns this table, consider leaving this empty.
        // Schema::dropIfExists('land_registry');
    }
};
