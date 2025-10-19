<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_postcode_deprivation');

        DB::statement(<<<'SQL'
CREATE VIEW v_postcode_deprivation AS
WITH base AS (
  SELECT
    REPLACE(UPPER(o.pcds), ' ', '') AS postcode_norm,
    o.pcds                           AS postcode_raw,
    o.lsoa11                         AS lsoa11_raw,
    o.lsoa21                         AS lsoa21_raw
  FROM onspd o
)
SELECT
  b.postcode_norm,
  b.postcode_raw,

  -- Effective keys after bridging (handles missing lsoa11 or lsoa21)
  COALESCE(b.lsoa11_raw, m21.LSOA11CD)        AS lsoa11cd,
  COALESCE(b.lsoa21_raw, m11.LSOA21CD)        AS lsoa21cd,

  -- IMD (2019) overall (joined on effective 2011 code)
  imd_dec.value                               AS imd_decile,   -- 1 = most deprived, 10 = least
  imd_rank.value                              AS imd_rank,

  -- 2021 LSOA enrichment (names, rural/urban, coords)
  g.LSOA21NM                                  AS lsoa21_name,
  g.RUC21CD,
  g.RUC21NM,
  g.Urban_rura,
  g.LAT,
  g.LONG

FROM base b
LEFT JOIN lsoa_2011_to_2021 m11
       ON m11.LSOA11CD = b.lsoa11_raw                 -- map 2011 -> 2021 if we have 2011
LEFT JOIN lsoa_2011_to_2021 m21
       ON m21.LSOA21CD = b.lsoa21_raw                 -- map 2021 -> 2011 if we only have 2021

LEFT JOIN imd2019 imd_dec
       ON imd_dec.FeatureCode = COALESCE(b.lsoa11_raw, m21.LSOA11CD)
      AND imd_dec.Measurement = 'Decile'
      AND imd_dec.`Indices_of_Deprivation` LIKE 'a. Index of Multiple Deprivation%'

LEFT JOIN imd2019 imd_rank
       ON imd_rank.FeatureCode = COALESCE(b.lsoa11_raw, m21.LSOA11CD)
      AND imd_rank.Measurement = 'Rank'
      AND imd_rank.`Indices_of_Deprivation` LIKE 'a. Index of Multiple Deprivation%'

LEFT JOIN lsoa21_ruc_geo g
       ON g.LSOA21CD = COALESCE(b.lsoa21_raw, m11.LSOA21CD);
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_postcode_deprivation');
    }
};
