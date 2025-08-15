<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('land_registry', function (Blueprint $table) {
            $table->index(['Postcode', 'PAON', 'Street', 'SAON'], 'idx_full_property');
            $table->index('Postcode', 'idx_postcode');
            $table->index('County', 'idx_county');
            $table->index('Date', 'idx_date');
        });
    }

    public function down(): void
    {
        Schema::table('land_registry', function (Blueprint $table) {
            $table->dropIndex('idx_full_property');
            $table->dropIndex('idx_postcode');
            $table->dropIndex('idx_county');
            $table->dropIndex('idx_date');
        });
    }
};
