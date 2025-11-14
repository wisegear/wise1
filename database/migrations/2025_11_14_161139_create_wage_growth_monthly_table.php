<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wage_growth_monthly', function (Blueprint $table) {
            $table->id();

            // YYYY-MM-01 date (unique for each monthly series entry)
            $table->date('date')->unique();

            // YoY % change, single-month (Total Pay, SA)
            $table->decimal('single_month_yoy', 5, 2)->nullable();

            // YoY % change, 3-month average (Total Pay, SA)
            $table->decimal('three_month_avg_yoy', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wage_growth_monthly');
    }
};
