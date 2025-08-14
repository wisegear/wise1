<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalise existing postcodes in both tables
        DB::statement("
            UPDATE land_registry 
            SET Postcode = UPPER(REPLACE(TRIM(Postcode), ' ', ''))
        ");

        DB::statement("
            UPDATE prime_postcodes 
            SET postcode = UPPER(REPLACE(TRIM(postcode), ' ', ''))
        ");
    }

    public function down(): void
    {
        // Can't really reverse without knowing original formatting
    }
};
