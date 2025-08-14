<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE prime_postcodes 
            MODIFY COLUMN category VARCHAR(50) 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        // Optional: revert to original collation
        DB::statement("
            ALTER TABLE prime_postcodes 
            MODIFY COLUMN category VARCHAR(50) 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_0900_ai_ci
        ");
    }
};
