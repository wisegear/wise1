<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adjust the GUID column to 38 chars (with braces), ASCII charset
        DB::statement("
            ALTER TABLE `land_registry`
            MODIFY `TransactionID` CHAR(38)
            CHARACTER SET ascii
            NOT NULL
        ");
    }

    public function down(): void
    {
        // Revert if needed (36 chars, no braces)
        DB::statement("
            ALTER TABLE `land_registry`
            MODIFY `TransactionID` CHAR(36)
            CHARACTER SET ascii
            NOT NULL
        ");
    }
};
