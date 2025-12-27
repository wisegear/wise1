#!/usr/bin/env bash
set -euo pipefail

# ==== CONFIG (LOCAL / HERD) ====
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="property"
DB_USER="root"

START_YEAR=2008
END_YEAR=$(date +%Y)

echo ">>> Upserting epc_staging into epc_certificates year by year (${START_YEAR}..${END_YEAR})"

for Y in $(seq "${START_YEAR}" "${END_YEAR}"); do
  echo ">>> Processing year ${Y}..."

  mysql --local-infile=1 -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${DB_NAME}" <<SQL
INSERT INTO epc_certificates (
  lmk_key, building_reference_number, uprn, uprn_source,
  postcode, address, posttown,
  inspection_date, lodgement_date, lodgement_datetime,
  local_authority, local_authority_label,
  property_type, built_form, construction_age_band,
  floor_level, flat_top_storey, flat_storey_count,
  current_energy_rating, potential_energy_rating,
  current_energy_efficiency, potential_energy_efficiency,
  total_floor_area,
  transaction_type, tenure,
  number_habitable_rooms, extension_count
)
SELECT
  s.LMK_KEY,
  NULLIF(s.BUILDING_REFERENCE_NUMBER,''),
  NULLIF(s.UPRN,'') + 0,
  NULLIF(s.UPRN_SOURCE,''),
  NULLIF(s.POSTCODE,''),
  NULLIF(
    TRIM(
      COALESCE(NULLIF(s.ADDRESS,''),
               NULLIF(CONCAT_WS(' ', NULLIF(s.ADDRESS1,''), NULLIF(s.ADDRESS2,''), NULLIF(s.ADDRESS3,'')), ''))
    ),
    ''
  ),
  NULLIF(s.POSTTOWN,''),
  STR_TO_DATE(NULLIF(s.INSPECTION_DATE,''), '%Y-%m-%d'),
  STR_TO_DATE(NULLIF(s.LODGEMENT_DATE,''), '%Y-%m-%d'),
  COALESCE(
    STR_TO_DATE(REPLACE(NULLIF(s.LODGEMENT_DATETIME,''), 'T',' '), '%Y-%m-%d %H:%i:%s'),
    STR_TO_DATE(NULLIF(s.LODGEMENT_DATE,''), '%Y-%m-%d')
  ),
  NULLIF(s.LOCAL_AUTHORITY,''),
  NULLIF(s.LOCAL_AUTHORITY_LABEL,''),
  NULLIF(s.PROPERTY_TYPE,''),
  NULLIF(s.BUILT_FORM,''),
  NULLIF(s.CONSTRUCTION_AGE_BAND,''),
  NULLIF(s.FLOOR_LEVEL,''),
  NULLIF(s.FLAT_TOP_STOREY,''),
  NULLIF(s.FLAT_STOREY_COUNT,'') + 0,
  NULLIF(s.CURRENT_ENERGY_RATING,''),
  NULLIF(s.POTENTIAL_ENERGY_RATING,''),
  NULLIF(s.CURRENT_ENERGY_EFFICIENCY,'') + 0,
  NULLIF(s.POTENTIAL_ENERGY_EFFICIENCY,'') + 0,
  NULLIF(s.TOTAL_FLOOR_AREA,'') + 0,
  NULLIF(s.TRANSACTION_TYPE,''),
  NULLIF(s.TENURE,''),
  CASE WHEN s.NUMBER_HABITABLE_ROOMS REGEXP '^[0-9]+$'
       THEN s.NUMBER_HABITABLE_ROOMS+0 ELSE NULL END,
  CASE WHEN s.EXTENSION_COUNT REGEXP '^[0-9]+$'
       THEN s.EXTENSION_COUNT+0 ELSE NULL END
FROM epc_staging s
WHERE s.LMK_KEY IS NOT NULL
  AND STR_TO_DATE(NULLIF(s.LODGEMENT_DATE,''), '%Y-%m-%d') >= '${Y}-01-01'
  AND STR_TO_DATE(NULLIF(s.LODGEMENT_DATE,''), '%Y-%m-%d') <  DATE_ADD('${Y}-01-01', INTERVAL 1 YEAR)
ON DUPLICATE KEY UPDATE
  building_reference_number   = VALUES(building_reference_number),
  uprn                        = VALUES(uprn),
  uprn_source                 = VALUES(uprn_source),
  postcode                    = VALUES(postcode),
  address                     = VALUES(address),
  posttown                    = VALUES(posttown),
  inspection_date             = VALUES(inspection_date),
  lodgement_date              = VALUES(lodgement_date),
  lodgement_datetime          = VALUES(lodgement_datetime),
  local_authority             = VALUES(local_authority),
  local_authority_label       = VALUES(local_authority_label),
  property_type               = VALUES(property_type),
  built_form                  = VALUES(built_form),
  construction_age_band       = VALUES(construction_age_band),
  floor_level                 = VALUES(floor_level),
  flat_top_storey             = VALUES(flat_top_storey),
  flat_storey_count           = VALUES(flat_storey_count),
  current_energy_rating       = VALUES(current_energy_rating),
  potential_energy_rating     = VALUES(potential_energy_rating),
  current_energy_efficiency   = VALUES(current_energy_efficiency),
  potential_energy_efficiency = VALUES(potential_energy_efficiency),
  total_floor_area            = VALUES(total_floor_area),
  transaction_type            = VALUES(transaction_type),
  tenure                      = VALUES(tenure),
  number_habitable_rooms      = VALUES(number_habitable_rooms),
  extension_count             = VALUES(extension_count);
SQL

  echo ">>> Year ${Y} complete."
done

echo ">>> Done."
