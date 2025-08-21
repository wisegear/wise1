<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE land_registry
            ADD INDEX idx_locality (Locality),
            ADD INDEX idx_locality_yeardate (Locality, YearDate)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE land_registry
            DROP INDEX idx_locality,
            DROP INDEX idx_locality_yeardate
        ");
    }
};
