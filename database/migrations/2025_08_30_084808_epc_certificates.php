<?php
// database/migrations/2025_08_30_100000_create_epc_certificates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('epc_certificates', function (Blueprint $t) {
            $t->engine('InnoDB');
            $t->charset('utf8mb4');
            $t->collation('utf8mb4_0900_ai_ci');

            // Identity / linkage
            $t->string('lmk_key', 128)->primary();
            $t->string('building_reference_number', 64)->nullable()->index();
            $t->unsignedBigInteger('uprn')->nullable()->index();
            $t->string('uprn_source', 32)->nullable();

            // Address
            $t->string('postcode', 16)->nullable()->index();
            $t->string('address', 512)->nullable();
            $t->string('posttown', 128)->nullable();

            // Dates
            $t->date('inspection_date')->nullable()->index();
            $t->date('lodgement_date')->nullable()->index();
            $t->dateTime('lodgement_datetime')->nullable()->index();

            // Geo labels
            $t->string('local_authority', 32)->nullable()->index();
            $t->string('local_authority_label', 128)->nullable();

            // Property meta
            $t->string('property_type', 64)->nullable()->index();
            $t->string('built_form', 64)->nullable();
            $t->string('construction_age_band', 64)->nullable();
            $t->string('floor_level', 16)->nullable();
            $t->string('flat_top_storey', 8)->nullable();
            $t->decimal('flat_storey_count', 4, 1)->nullable();

            // Headline metrics
            $t->string('current_energy_rating', 8)->nullable();
            $t->string('potential_energy_rating', 8)->nullable();
            $t->unsignedSmallInteger('current_energy_efficiency')->nullable();
            $t->unsignedSmallInteger('potential_energy_efficiency')->nullable();
            $t->decimal('total_floor_area', 8, 2)->nullable();

            // Context
            $t->string('transaction_type', 255)->nullable();
            $t->string('tenure', 255)->nullable();
            $t->unsignedSmallInteger('number_habitable_rooms')->nullable();
            $t->unsignedSmallInteger('extension_count')->nullable();

            $t->timestamps();

            // Composite index for common lookups (post code + date)
            $t->index(['postcode', 'lodgement_date'], 'epc_postcode_lodged_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('epc_certificates');
    }
};
