<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOnsud extends Command
{
    protected $signature = 'onsud:import {path : Path to ONSUD Data folder} {--truncate : Truncate the onsud table before import}';
    protected $description = 'Bulk import ONSUD regional CSV files into the onsud table in a deterministic order';

    public function handle(): int
    {
        $path = rtrim((string) $this->argument('path'), DIRECTORY_SEPARATOR);

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return self::FAILURE;
        }

        // Ensure LOCAL INFILE is enabled for bulk import.
        DB::statement('SET GLOBAL local_infile = 1');

        if ($this->option('truncate')) {
            DB::table('onsud')->truncate();
            $this->info('Truncated onsud table.');
        }

        $files = glob($path . DIRECTORY_SEPARATOR . '*.csv');
        if (empty($files)) {
            $this->warn("No CSV files found in {$path}");
            return self::SUCCESS;
        }

        $ordered = $this->orderFiles($files);
        $expectedCount = count($ordered);
        $this->info("Found {$expectedCount} CSV file(s) to import.");

        $columns = [
            'UPRN',
            'GRIDGB1E',
            'GRIDGB1N',
            'PCDS',
            'CTY25CD',
            'CED25CD',
            'LAD25CD',
            'WD25CD',
            'PARNCP25CD',
            'HLTH19CD',
            'ctry25cd',
            'RGN25CD',
            'PCON24CD',
            'EER20CD',
            'ttwa15cd',
            'itl25cd',
            'NPARK16CD',
            'OA21CD',
            'lsoa21cd',
            'msoa21cd',
            'WZ11CD',
            'SICBL24CD',
            'BUA24CD',
            'BUASD11CD',
            'ruc21ind',
            'oac21ind',
            'lep21cd1',
            'lep21cd2',
            'pfa23cd',
            'imd19ind',
        ];

        foreach ($ordered as $file) {
            $base = basename($file);
            $this->info("Importing {$base}...");

            $sample = @file_get_contents($file, false, null, 0, 200000) ?: '';
            $lineTerm = (strpos($sample, "\r\n") !== false) ? "\\r\\n" : "\\n";

            $columnsSql = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

            $sql = "
                LOAD DATA LOCAL INFILE '" . addslashes($file) . "'
                INTO TABLE onsud
                CHARACTER SET utf8mb4
                FIELDS TERMINATED BY ','
                ENCLOSED BY '\"'
                ESCAPED BY '\\\\'
                LINES TERMINATED BY '{$lineTerm}'
                IGNORE 1 LINES
                ({$columnsSql})
            ";

            $sql .= " SET GRIDGB1E = NULLIF(GRIDGB1E, ''), GRIDGB1N = NULLIF(GRIDGB1N, ''), imd19ind = NULLIF(imd19ind, '')";

            try {
                DB::connection()->getPdo()->exec($sql);
            } catch (\Exception $e) {
                $this->error("Failed on {$base}: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info('All files imported.');
        return self::SUCCESS;
    }

    private function orderFiles(array $files): array
    {
        $order = ['EE', 'EM', 'LN', 'NE', 'NW', 'SC', 'SE', 'SW', 'WA', 'WM', 'YH'];
        $map = [];

        foreach ($files as $file) {
            $base = basename($file);
            if (preg_match('/_([A-Z]{2})\\.csv$/', $base, $m)) {
                $map[$m[1]] = $file;
            } else {
                $map[$base] = $file;
            }
        }

        $ordered = [];
        foreach ($order as $key) {
            if (isset($map[$key])) {
                $ordered[] = $map[$key];
                unset($map[$key]);
            }
        }

        if (!empty($map)) {
            $remaining = array_values($map);
            sort($remaining);
            $ordered = array_merge($ordered, $remaining);
        }

        return $ordered;
    }
}
