<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add generated normalized columns and composite index for sargable queries
        DB::statement(<<<SQL
            ALTER TABLE imd2019
            ADD COLUMN measurement_norm VARCHAR(16)
              GENERATED ALWAYS AS (LOWER(TRIM(Measurement))) STORED,
            ADD COLUMN iod_norm VARCHAR(160)
              GENERATED ALWAYS AS (LOWER(TRIM(`Indices_of_Deprivation`))) STORED
        SQL);

        DB::statement(<<<SQL
            CREATE INDEX idx_imd_norm_combo
              ON imd2019 (FeatureCode, measurement_norm, iod_norm(64));
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX idx_imd_norm_combo ON imd2019');
        DB::statement('ALTER TABLE imd2019 DROP COLUMN measurement_norm, DROP COLUMN iod_norm');
    }
};
