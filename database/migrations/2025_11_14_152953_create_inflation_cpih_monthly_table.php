<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inflation_cpih_monthly', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();          // YYYY-MM-01
            $table->decimal('rate', 5, 2);           // e.g. 4.10 (%) 12-month change
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inflation_cpih_monthly');
    }
};
