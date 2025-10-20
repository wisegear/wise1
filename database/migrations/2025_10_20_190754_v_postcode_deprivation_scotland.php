<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement(<<<SQL
CREATE OR REPLACE VIEW v_postcode_deprivation_scotland AS
SELECT
    o.pcds  AS postcode,
    o.lsoa11 AS data_zone,  -- S010… in Scotland
    s.Council_area,
    s.Intermediate_Zone,
    s.SIMD2020v2_Rank                         AS `rank`,
    s.SIMD2020v2_Decile                       AS decile,
    s.SIMD2020v2_Income_Domain_Rank           AS income_rank,
    s.SIMD2020_Employment_Domain_Rank         AS employment_rank,
    s.SIMD2020_Health_Domain_Rank             AS health_rank,
    s.SIMD2020_Education_Domain_Rank          AS education_rank,
    s.SIMD2020_Crime_Domain_Rank              AS crime_rank,
    s.SIMD2020_Access_Domain_Rank             AS access_rank,
    s.SIMD2020_Housing_Domain_Rank            AS housing_rank,
    o.lat, o.`long`
FROM onspd o
JOIN simd2020 s
  ON o.lsoa11 = s.Data_Zone
WHERE o.ctry = 'S92000003';
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_postcode_deprivation_scotland');
    }
};
