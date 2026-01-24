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
        $current = $this->findColumn();
        if ($current === null || $current === 'BUILDING_REFERENCE_NUMBER') {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `epc_certificates_scotland` CHANGE COLUMN `%s` `BUILDING_REFERENCE_NUMBER` TEXT NULL',
            str_replace('`', '``', $current)
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $current = $this->findColumn();
        if ($current === null || $current !== 'BUILDING_REFERENCE_NUMBER') {
            return;
        }

        $bom = "\xEF\xBB\xBF";
        DB::statement(sprintf(
            'ALTER TABLE `epc_certificates_scotland` CHANGE COLUMN `BUILDING_REFERENCE_NUMBER` `%sBUILDING_REFERENCE_NUMBER` TEXT NULL',
            $bom
        ));
    }

    private function findColumn(): ?string
    {
        $rows = DB::select(<<<SQL
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'epc_certificates_scotland'
              AND COLUMN_NAME LIKE '%BUILDING_REFERENCE_NUMBER%'
        SQL);

        if (empty($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if ($row->COLUMN_NAME === 'BUILDING_REFERENCE_NUMBER') {
                return 'BUILDING_REFERENCE_NUMBER';
            }
        }

        return $rows[0]->COLUMN_NAME;
    }
};
