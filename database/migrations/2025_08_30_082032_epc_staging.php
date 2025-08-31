<?php

// database/migrations/2025_08_30_000000_create_epc_staging_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epc_staging', function (Blueprint $t) {
            $t->engine('InnoDB');
            $t->charset('utf8mb4');
            $t->collation('utf8mb4_0900_ai_ci');

            // Keep source header names for frictionless LOAD DATA
            $t->string('LMK_KEY', 128)->nullable();
            $t->text('ADDRESS1')->nullable();
            $t->text('ADDRESS2')->nullable();
            $t->text('ADDRESS3')->nullable();
            $t->string('POSTCODE', 16)->nullable();
            $t->string('BUILDING_REFERENCE_NUMBER', 64)->nullable();
            $t->string('CURRENT_ENERGY_RATING', 8)->nullable();
            $t->string('POTENTIAL_ENERGY_RATING', 8)->nullable();
            $t->string('CURRENT_ENERGY_EFFICIENCY', 32)->nullable();
            $t->string('POTENTIAL_ENERGY_EFFICIENCY', 32)->nullable();
            $t->string('PROPERTY_TYPE', 255)->nullable();
            $t->string('BUILT_FORM', 255)->nullable();
            $t->string('INSPECTION_DATE', 32)->nullable();
            $t->string('LOCAL_AUTHORITY', 255)->nullable();
            $t->string('CONSTITUENCY', 255)->nullable();
            $t->string('COUNTY', 255)->nullable();
            $t->string('LODGEMENT_DATE', 32)->nullable();
            $t->string('TRANSACTION_TYPE', 255)->nullable();
            $t->string('ENVIRONMENT_IMPACT_CURRENT', 255)->nullable();
            $t->string('ENVIRONMENT_IMPACT_POTENTIAL', 255)->nullable();
            $t->string('ENERGY_CONSUMPTION_CURRENT', 255)->nullable();
            $t->string('ENERGY_CONSUMPTION_POTENTIAL', 255)->nullable();
            $t->string('CO2_EMISSIONS_CURRENT', 255)->nullable();
            $t->string('CO2_EMISS_CURR_PER_FLOOR_AREA', 255)->nullable();
            $t->string('CO2_EMISSIONS_POTENTIAL', 255)->nullable();
            $t->string('LIGHTING_COST_CURRENT', 255)->nullable();
            $t->string('LIGHTING_COST_POTENTIAL', 255)->nullable();
            $t->string('HEATING_COST_CURRENT', 255)->nullable();
            $t->string('HEATING_COST_POTENTIAL', 255)->nullable();
            $t->string('HOT_WATER_COST_CURRENT', 255)->nullable();
            $t->string('HOT_WATER_COST_POTENTIAL', 255)->nullable();
            $t->string('TOTAL_FLOOR_AREA', 64)->nullable();
            $t->string('ENERGY_TARIFF', 255)->nullable();
            $t->string('MAINS_GAS_FLAG', 8)->nullable();
            $t->string('FLOOR_LEVEL', 16)->nullable();
            $t->string('FLAT_TOP_STOREY', 8)->nullable();
            $t->string('FLAT_STOREY_COUNT', 16)->nullable();
            $t->string('MAIN_HEATING_CONTROLS', 255)->nullable();
            $t->string('MULTI_GLAZE_PROPORTION', 255)->nullable();
            $t->string('GLAZED_TYPE', 255)->nullable();
            $t->string('GLAZED_AREA', 255)->nullable();
            $t->string('EXTENSION_COUNT', 16)->nullable();
            $t->string('NUMBER_HABITABLE_ROOMS', 16)->nullable();
            $t->string('NUMBER_HEATED_ROOMS', 16)->nullable();
            $t->string('LOW_ENERGY_LIGHTING', 16)->nullable();
            $t->string('NUMBER_OPEN_FIREPLACES', 16)->nullable();
            $t->text('HOTWATER_DESCRIPTION')->nullable();
            $t->string('HOT_WATER_ENERGY_EFF', 32)->nullable();
            $t->string('HOT_WATER_ENV_EFF', 32)->nullable();
            $t->text('FLOOR_DESCRIPTION')->nullable();
            $t->string('FLOOR_ENERGY_EFF', 32)->nullable();
            $t->string('FLOOR_ENV_EFF', 32)->nullable();
            $t->text('WINDOWS_DESCRIPTION')->nullable();
            $t->string('WINDOWS_ENERGY_EFF', 32)->nullable();
            $t->string('WINDOWS_ENV_EFF', 32)->nullable();
            $t->text('WALLS_DESCRIPTION')->nullable();
            $t->string('WALLS_ENERGY_EFF', 32)->nullable();
            $t->string('WALLS_ENV_EFF', 32)->nullable();
            $t->text('SECONDHEAT_DESCRIPTION')->nullable();
            $t->string('SHEATING_ENERGY_EFF', 32)->nullable();
            $t->string('SHEATING_ENV_EFF', 32)->nullable();
            $t->text('ROOF_DESCRIPTION')->nullable();
            $t->string('ROOF_ENERGY_EFF', 32)->nullable();
            $t->string('ROOF_ENV_EFF', 32)->nullable();
            $t->text('MAINHEAT_DESCRIPTION')->nullable();
            $t->string('MAINHEAT_ENERGY_EFF', 32)->nullable();
            $t->string('MAINHEAT_ENV_EFF', 32)->nullable();
            $t->text('MAINHEATCONT_DESCRIPTION')->nullable();
            $t->string('MAINHEATC_ENERGY_EFF', 32)->nullable();
            $t->string('MAINHEATC_ENV_EFF', 32)->nullable();
            $t->text('LIGHTING_DESCRIPTION')->nullable();
            $t->string('LIGHTING_ENERGY_EFF', 32)->nullable();
            $t->string('LIGHTING_ENV_EFF', 32)->nullable();
            $t->string('MAIN_FUEL', 255)->nullable();
            $t->string('WIND_TURBINE_COUNT', 16)->nullable();
            $t->string('HEAT_LOSS_CORRIDOR', 255)->nullable();
            $t->string('UNHEATED_CORRIDOR_LENGTH', 255)->nullable();
            $t->string('FLOOR_HEIGHT', 255)->nullable();
            $t->string('PHOTO_SUPPLY', 255)->nullable();
            $t->string('SOLAR_WATER_HEATING_FLAG', 8)->nullable();
            $t->string('MECHANICAL_VENTILATION', 255)->nullable();
            $t->text('ADDRESS')->nullable();
            $t->string('LOCAL_AUTHORITY_LABEL', 255)->nullable();
            $t->string('CONSTITUENCY_LABEL', 255)->nullable();
            $t->string('POSTTOWN', 255)->nullable();
            $t->string('CONSTRUCTION_AGE_BAND', 255)->nullable();
            $t->string('LODGEMENT_DATETIME', 32)->nullable();
            $t->string('TENURE', 255)->nullable();
            $t->string('FIXED_LIGHTING_OUTLETS_COUNT', 16)->nullable();
            $t->string('LOW_ENERGY_FIXED_LIGHT_COUNT', 16)->nullable();
            $t->string('UPRN', 32)->nullable();
            $t->string('UPRN_SOURCE', 32)->nullable();
            $t->string('REPORT_TYPE', 16)->nullable();

            // optional loader bookkeeping
            $t->timestamp('loaded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epc_staging');
    }
};
