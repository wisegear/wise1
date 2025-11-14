<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unemployment_monthly', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();   // YYYY-MM-01
            $table->decimal('rate', 4, 2);    // e.g. 4.2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unemployment_monthly');
    }
};
