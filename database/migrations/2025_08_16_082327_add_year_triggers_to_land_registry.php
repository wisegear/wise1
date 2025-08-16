<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Be idempotent
        DB::unprepared('DROP TRIGGER IF EXISTS bi_land_registry_set_yeardate;');
        DB::unprepared('DROP TRIGGER IF EXISTS bu_land_registry_set_yeardate;');

        // Before INSERT: compute YearDate from Date
        DB::unprepared(<<<'SQL'
CREATE TRIGGER bi_land_registry_set_yeardate
BEFORE INSERT ON land_registry
FOR EACH ROW
BEGIN
    SET NEW.YearDate = CASE
        WHEN NEW.`Date` IS NOT NULL THEN YEAR(NEW.`Date`)
        ELSE NULL
    END;
END
SQL);

        // Before UPDATE: keep YearDate in sync if Date changes
        DB::unprepared(<<<'SQL'
CREATE TRIGGER bu_land_registry_set_yeardate
BEFORE UPDATE ON land_registry
FOR EACH ROW
BEGIN
    SET NEW.YearDate = CASE
        WHEN NEW.`Date` IS NOT NULL THEN YEAR(NEW.`Date`)
        ELSE NULL
    END;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS bi_land_registry_set_yeardate;');
        DB::unprepared('DROP TRIGGER IF EXISTS bu_land_registry_set_yeardate;');
    }
};
