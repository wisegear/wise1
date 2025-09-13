<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epc_certificates_scotland', function (Blueprint $table) {
            $table->id();
            $table->text('﻿BUILDING_REFERENCE_NUMBER')->nullable();
            $table->text('OSG_REFERENCE_NUMBER')->nullable();
            $table->text('ADDRESS1')->nullable();
            $table->text('ADDRESS2')->nullable();
            $table->text('ADDRESS3')->nullable();
            $table->string('POSTCODE')->nullable();
            $table->text('INSPECTION_DATE')->nullable();
            $table->text('TYPE_OF_ASSESSMENT')->nullable();
            $table->string('LODGEMENT_DATE')->nullable();
            $table->text('ENERGY_CONSUMPTION_CURRENT')->nullable();
            $table->text('TOTAL_FLOOR_AREA')->nullable();
            $table->text('3_YR_ENERGY_COST_CURRENT')->nullable();
            $table->text('3_YR_ENERGY_SAVINGS_POTENTIAL')->nullable();
            $table->text('CURRENT_ENERGY_EFFICIENCY')->nullable();
            $table->text('CURRENT_ENERGY_RATING')->nullable();
            $table->text('POTENTIAL_ENERGY_EFFICIENCY')->nullable();
            $table->text('POTENTIAL_ENERGY_RATING')->nullable();
            $table->text('ENVIRONMENT_IMPACT_CURRENT')->nullable();
            $table->text('CURRENT_ENVIRONMENTAL_RATING')->nullable();
            $table->text('ENVIRONMENT_IMPACT_POTENTIAL')->nullable();
            $table->text('POTENTIAL_ENVIRONMENTAL_RATING')->nullable();
            $table->text('CO2_EMISS_CURR_PER_FLOOR_AREA')->nullable();
            $table->text('IMPROVEMENTS')->nullable();
            $table->text('WALL_DESCRIPTION')->nullable();
            $table->text('WALL_ENERGY_EFF')->nullable();
            $table->text('WALL_ENV_EFF')->nullable();
            $table->text('ROOF_DESCRIPTION')->nullable();
            $table->text('ROOF_ENERGY_EFF')->nullable();
            $table->text('ROOF_ENV_EFF')->nullable();
            $table->text('FLOOR_DESCRIPTION')->nullable();
            $table->text('FLOOR_ENERGY_EFF')->nullable();
            $table->text('FLOOR_ENV_EFF')->nullable();
            $table->text('WINDOWS_DESCRIPTION')->nullable();
            $table->text('WINDOWS_ENERGY_EFF')->nullable();
            $table->text('WINDOWS_ENV_EFF')->nullable();
            $table->text('MAINHEAT_DESCRIPTION')->nullable();
            $table->text('MAINHEAT_ENERGY_EFF')->nullable();
            $table->text('MAINHEAT_ENV_EFF')->nullable();
            $table->text('MAINHEATCONT_DESCRIPTION')->nullable();
            $table->text('MAINHEATC_ENERGY_EFF')->nullable();
            $table->text('MAINHEATC_ENV_EFF')->nullable();
            $table->text('SECONDHEAT_DESCRIPTION')->nullable();
            $table->text('SHEATING_ENERGY_EFF')->nullable();
            $table->text('SHEATING_ENV_EFF')->nullable();
            $table->text('HOTWATER_DESCRIPTION')->nullable();
            $table->text('HOT_WATER_ENERGY_EFF')->nullable();
            $table->text('HOT_WATER_ENV_EFF')->nullable();
            $table->text('LIGHTING_DESCRIPTION')->nullable();
            $table->text('LIGHTING_ENERGY_EFF')->nullable();
            $table->text('LIGHTING_ENV_EFF')->nullable();
            $table->text('AIR_TIGHTNESS_DESCRIPTION')->nullable();
            $table->text('AIR_TIGHTNESS_ENERGY_EFF')->nullable();
            $table->text('AIR_TIGHTNESS_ENV_EFF')->nullable();
            $table->text('CO2_EMISSIONS_CURRENT')->nullable();
            $table->text('CO2_EMISSIONS_POTENTIAL')->nullable();
            $table->text('HEATING_COST_CURRENT')->nullable();
            $table->text('HEATING_COST_POTENTIAL')->nullable();
            $table->text('HOT_WATER_COST_CURRENT')->nullable();
            $table->text('HOT_WATER_COST_POTENTIAL')->nullable();
            $table->text('LIGHTING_COST_CURRENT')->nullable();
            $table->text('LIGHTING_COST_POTENTIAL')->nullable();
            $table->text('ALTERNATIVE_IMPROVEMENTS')->nullable();
            $table->text('LZC_ENERGY_SOURCES')->nullable();
            $table->text('SPACE_HEATING_DEMAND')->nullable();
            $table->text('WATER_HEATING_DEMAND')->nullable();
            $table->text('IMPACT_LOFT_INSULATION')->nullable();
            $table->text('IMPACT_CAVITY_WALL_INSULATION')->nullable();
            $table->text('IMPACT_SOLID_WALL_INSULATION')->nullable();
            $table->text('ADDENDUM_TEXT')->nullable();
            $table->text('CONSTRUCTION_AGE_BAND')->nullable();
            $table->text('FLOOR_HEIGHT')->nullable();
            $table->text('DATA_ZONE')->nullable();
            $table->text('ENERGY_CONSUMPTION_POTENTIAL')->nullable();
            $table->text('EXTENSION_COUNT')->nullable();
            $table->text('FIXED_LIGHTING_OUTLETS_COUNT')->nullable();
            $table->text('LOW_ENERGY_FIXED_LIGHT_COUNT')->nullable();
            $table->text('LOW_ENERGY_LIGHTING')->nullable();
            $table->text('FLOOR_LEVEL')->nullable();
            $table->text('FLAT_TOP_STOREY')->nullable();
            $table->text('GLAZED_AREA')->nullable();
            $table->text('NUMBER_HABITABLE_ROOMS')->nullable();
            $table->text('HEAT_LOSS_CORRIDOOR')->nullable();
            $table->text('NUMBER_HEATED_ROOMS')->nullable();
            $table->text('LOCAL_AUTHORITY_LABEL')->nullable();
            $table->text('MAINS_GAS_FLAG')->nullable();
            $table->text('MAIN_HEATING_CATEGORY')->nullable();
            $table->text('MAIN_FUEL')->nullable();
            $table->text('MAIN_HEATING_CONTROLS')->nullable();
            $table->text('MECHANICAL_VENTILATION')->nullable();
            $table->text('ENERGY_TARIFF')->nullable();
            $table->text('MULTI_GLAZE_PROPORTION')->nullable();
            $table->text('GLAZED_TYPE')->nullable();
            $table->text('NUMBER_OPEN_FIREPLACES')->nullable();
            $table->text('PHOTO_SUPPLY')->nullable();
            $table->text('SOLAR_WATER_HEATING_FLAG')->nullable();
            $table->text('TENURE')->nullable();
            $table->text('TRANSACTION_TYPE')->nullable();
            $table->text('UNHEATED_CORRIDOR_LENGTH')->nullable();
            $table->text('CONSTITUENCY')->nullable();
            $table->text('CONSTITUENCY_LABEL')->nullable();
            $table->text('WIND_TURBINE_COUNT')->nullable();
            $table->text('BUILT_FORM')->nullable();
            $table->text('PROPERTY_TYPE')->nullable();
            $table->text('DATA_ZONE_2011')->nullable();
            $table->text('CREATED_AT')->nullable();
            $table->string('REPORT_REFERENCE_NUMBER')->nullable();
            $table->string('source_file')->nullable();

            // Useful indexes (ensure these are string columns)
            $table->index('POSTCODE');
            $table->index('REPORT_REFERENCE_NUMBER');
            $table->index('LODGEMENT_DATE');
        });

        // Safety: if any columns were accidentally created with a UTF‑8 BOM prefix (EF BB BF),
        // rename them to the clean version while preserving the original column type/nullability.
        $badCols = DB::select(<<<SQL
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'epc_certificates_scotland'
              AND HEX(COLUMN_NAME) LIKE 'EFBBBF%'
        SQL);

        foreach ($badCols as $col) {
            // Remove BOM from the beginning of the name
            $clean = preg_replace('/^\xEF\xBB\xBF/u', '', $col->COLUMN_NAME);
            $nullSql = ($col->IS_NULLABLE === 'YES') ? 'NULL' : 'NOT NULL';
            // Use COLUMN_TYPE to preserve length/precision/etc.
            $sql = sprintf(
                'ALTER TABLE `epc_certificates_scotland` CHANGE COLUMN `%s` `%s` %s %s',
                $col->COLUMN_NAME,
                $clean,
                $col->COLUMN_TYPE,
                $nullSql
            );
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('epc_certificates_scotland');
    }
};
