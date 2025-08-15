<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up(): void
    {
        if (!Schema::hasColumn('land_registry', 'YearDate')) {
            Schema::table('land_registry', function (Blueprint $table) {
                $table->year('YearDate')->nullable()->after('Date');
            });
        }

        // Backfill YearDate using year-by-year ranges (leverages idx_date)
        $bounds = DB::selectOne("
            SELECT MIN(YEAR(`Date`)) AS miny, MAX(YEAR(`Date`)) AS maxy
            FROM land_registry
            WHERE `Date` IS NOT NULL
        ");

        if ($bounds && $bounds->miny !== null && $bounds->maxy !== null) {
            // Ensure safe updates won't block range UPDATEs
            DB::statement('SET SQL_SAFE_UPDATES=0');

            $start = (int) $bounds->miny;
            $end   = (int) $bounds->maxy;

            for ($y = $start; $y <= $end; $y++) {
                DB::statement("
                    UPDATE land_registry
                    SET YearDate = {$y}
                    WHERE YearDate IS NULL
                      AND `Date` >= '{$y}-01-01' AND `Date` < '".($y+1)."-01-01'
                ");
            }
        }

        Schema::table('land_registry', function (Blueprint $table) {
            $table->index('YearDate', 'idx_yeardate');
            $table->index(['County', 'YearDate'], 'idx_county_yeardate');
            $table->index(['Postcode', 'YearDate'], 'idx_postcode_yeardate');
        });
    }

    public function down(): void
    {
        Schema::table('land_registry', function (Blueprint $table) {
            $table->dropIndex('idx_yeardate');
            $table->dropIndex('idx_county_yeardate');
            $table->dropIndex('idx_postcode_yeardate');
            $table->dropColumn('YearDate');
        });
    }
};
