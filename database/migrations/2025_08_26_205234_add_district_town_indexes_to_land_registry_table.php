<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // These match your WHERE + GROUP BY patterns
        Schema::table('land_registry', function (Blueprint $table) {
            // District-level
            $table->index(['District', 'PPDCategoryType', 'YearDate'], 'idx_district_ppd_year');
            $table->index(['District', 'PPDCategoryType', 'PropertyType'], 'idx_district_ppd_type');

            // Town/City-level
            $table->index(['TownCity', 'PPDCategoryType', 'YearDate'], 'idx_town_ppd_year');
            $table->index(['TownCity', 'PPDCategoryType', 'PropertyType'], 'idx_town_ppd_type');
        });
    }

    public function down(): void
    {
        Schema::table('land_registry', function (Blueprint $table) {
            $table->dropIndex('idx_district_ppd_year');
            $table->dropIndex('idx_district_ppd_type');
            $table->dropIndex('idx_town_ppd_year');
            $table->dropIndex('idx_town_ppd_type');
        });
    }
};
