<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onsud', function (Blueprint $table) {
            $table->index(['ctry25cd', 'GRIDGB1E', 'GRIDGB1N'], 'onsud_ctry_east_north_idx');
            $table->index(['GRIDGB1E', 'GRIDGB1N'], 'onsud_east_north_idx');
        });
    }

    public function down(): void
    {
        Schema::table('onsud', function (Blueprint $table) {
            $table->dropIndex('onsud_ctry_east_north_idx');
            $table->dropIndex('onsud_east_north_idx');
        });
    }
};
