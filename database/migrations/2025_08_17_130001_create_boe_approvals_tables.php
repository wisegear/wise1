<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mortgage_approvals', function (Blueprint $t) {
            $t->id();
            $t->string('series_code', 32);   // e.g. LPMVTVX
            $t->date('period');              // month date (YYYY-MM-01)
            $t->unsignedInteger('value')->nullable(); // approvals are counts
            $t->string('unit', 16)->nullable();       // optional (e.g. "count")
            $t->string('source', 64)->default('BoE');
            $t->timestamps();

            $t->unique(['series_code', 'period']);
            $t->index('period');
            $t->index('series_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mortgage_approvals');
    }
};
