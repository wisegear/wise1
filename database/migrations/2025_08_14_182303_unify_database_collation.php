<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // 1. Change the database default collation
        $databaseName = DB::getDatabaseName();
        DB::statement("ALTER DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 2. Convert each table
        $tables = DB::select('SHOW TABLES');

        foreach ($tables as $table) {
            $tableArray = (array) $table;
            $tableName = array_values($tableArray)[0];
            DB::statement("ALTER TABLE `$tableName` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Optional: revert database to utf8mb4_0900_ai_ci
        $databaseName = DB::getDatabaseName();
        DB::statement("ALTER DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    }
};
