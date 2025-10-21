<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Wales has 1,909 LSOAs in WIMD 2019.
        // Decile is computed as CEIL(rank / 190.9) -> 1..10
        DB::statement(<<<SQL
            CREATE OR REPLACE VIEW v_postcode_deprivation_wales AS
            SELECT
                o.pcds                           AS postcode,
                o.lsoa11                         AS lsoa_code,      -- Wales LSOA code
                w.LSOA_name                      AS lsoa_name,
                w.Local_authority_name           AS local_authority_name,
                w.WIMD_2019                      AS `rank`,         -- 1 = most deprived ... 1909 = least
                CEIL(w.WIMD_2019 / 190.9)        AS decile,         -- computed decile 1..10
                w.Income                         AS income_rank,
                w.Employment                     AS employment_rank,
                w.Health                         AS health_rank,
                w.Education                      AS education_rank,
                w.Access_to_services             AS access_rank,
                w.Housing                        AS housing_rank,
                w.Community_safety               AS community_safety_rank,
                w.Physical_environment           AS physical_environment_rank,
                o.lat,
                o.`long`
            FROM onspd o
            JOIN wimd2019 w
              ON o.lsoa11 = w.LSOA_code
            WHERE o.ctry = 'W92000004'  -- Wales only
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_postcode_deprivation_wales');
    }
};
