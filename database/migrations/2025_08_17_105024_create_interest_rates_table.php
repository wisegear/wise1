<?php
// database/migrations/2025_08_17_000000_create_interest_rates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interest_rates', function (Blueprint $table) {
            $table->id();

            // The date the Bank Rate changed / became effective
            $table->date('effective_date');

            // Bank Rate as a percentage, e.g. 5.25 (not basis points)
            $table->decimal('rate', 5, 2);

            // Optional metadata if you ever need it
            $table->string('source', 64)->default('BoE Bank Rate');
            $table->text('notes')->nullable();

            $table->timestamps();

            // One change per date
            $table->unique('effective_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_rates');
    }
};
