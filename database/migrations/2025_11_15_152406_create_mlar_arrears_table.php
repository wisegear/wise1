<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mlar_arrears', function (Blueprint $table) {
            $table->id();
            $table->string('band');           // e.g. 1.5_2.5
            $table->string('description');    // full text description
            $table->integer('year');          // 2007
            $table->string('quarter');        // Q1
            $table->decimal('value', 5, 3);   // e.g. 0.589
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mlar_arrears');
    }
};
