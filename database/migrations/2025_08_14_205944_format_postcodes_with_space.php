<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add space before the last 3 characters of the postcode
        DB::statement("
            UPDATE land_registry
            SET Postcode = CONCAT(
                LEFT(Postcode, LENGTH(Postcode) - 3),
                ' ',
                RIGHT(Postcode, 3)
            )
        ");
    }

    public function down(): void
    {
        // Remove spaces just in case we roll back
        DB::statement("
            UPDATE land_registry
            SET Postcode = REPLACE(Postcode, ' ', '')
        ");
    }
};
