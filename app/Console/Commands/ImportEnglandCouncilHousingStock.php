<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportEnglandCouncilHousingStock extends Command
{
    protected $signature = 'app:import-england-council-housing-stock
                            {--path= : Absolute path to CSV file}
                            {--truncate : Truncate the table before import}
                            {--chunk=2000 : Rows per insert batch}';

    protected $description = 'Import England council housing stock (simplified LAHS extract) from CSV';

    public function handle(): int
    {
        $path = (string) $this->option('path');

        if (!$path) {
            $this->error('Missing --path=/full/path/to/file.csv');
            return self::FAILURE;
        }

        if (!is_file($path) || !is_readable($path)) {
            $this->error("CSV not found or not readable: {$path}");
            return self::FAILURE;
        }

        $chunkSize = max(100, (int) $this->option('chunk'));

        DB::connection()->disableQueryLog();

        if ($this->option('truncate')) {
            $this->warn('Truncating england_council_housing_stock...');
            DB::table('england_council_housing_stock')->truncate();
        }

        $this->info("Importing: {$path}");
        $this->info("Chunk size: {$chunkSize}");

        $file = new \SplFileObject($path);
        $file->setFlags(
            \SplFileObject::READ_CSV |
            \SplFileObject::SKIP_EMPTY |
            \SplFileObject::DROP_NEW_LINE
        );

        // Read header row
        $header = $file->fgetcsv();
        if (!$header || !is_array($header)) {
            $this->error('Could not read header row.');
            return self::FAILURE;
        }

        // Normalise headers to make matching robust
        $norm = fn($v) => strtolower(trim((string) $v));
        $headerNorm = array_map($norm, $header);
        $idx = array_flip($headerNorm);

        // Helper to pull a column if it exists
        $get = function (array $row, string $col) use ($idx) {
            $key = strtolower(trim($col));
            if (!array_key_exists($key, $idx)) return null;
            $i = $idx[$key];
            return array_key_exists($i, $row) ? $row[$i] : null;
        };

        // Required columns (based on your screenshot)
        $required = [
            'local_authority',
            'local_authority_code',
            'LAD23NM',
            'LAD23CD',
            'LAD23TYPE',
            'region_name',
            'region_code',
            'county_name',
            'county_code',
            'Year',
            'status',
            'Total_stock',
            'New_Builds',
            'Acquisitions',
        ];

        // Check we have them (case-insensitive because we normalised)
        $missing = [];
        foreach ($required as $col) {
            if (!array_key_exists(strtolower(trim($col)), $idx)) {
                $missing[] = $col;
            }
        }

        if ($missing) {
            $this->error('CSV is missing required columns: ' . implode(', ', $missing));
            $this->line('Tip: check the column names match your exported/simplified CSV exactly.');
            return self::FAILURE;
        }

        $toInt = function ($v): ?int {
            $v = trim((string) $v);
            if ($v === '') return null;

            // Remove commas and any stray spaces
            $v = str_replace([',', ' '], '', $v);

            // Some datasets use "0" or "0.0" etc.
            if (!is_numeric($v)) return null;

            return (int) round((float) $v);
        };

        $clean = fn($v) => ($v === null) ? null : trim((string) $v);

        $rows = [];
        $count = 0;
        $inserted = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        // Iterate remaining rows
        foreach ($file as $row) {
            if (!is_array($row) || count($row) < 2) {
                continue;
            }

            // Skip accidental repeated header lines
            if (strtolower(trim((string)($row[0] ?? ''))) === 'local_authority') {
                continue;
            }

            $payload = [
                'local_authority'       => $clean($get($row, 'local_authority')),
                'local_authority_code'  => $clean($get($row, 'local_authority_code')),

                'lad23_name'            => $clean($get($row, 'LAD23NM')),
                'lad23_code'            => $clean($get($row, 'LAD23CD')),
                'lad23_type'            => $clean($get($row, 'LAD23TYPE')),

                'region_name'           => $clean($get($row, 'region_name')),
                'region_code'           => $clean($get($row, 'region_code')),

                'county_name'           => $clean($get($row, 'county_name')),
                'county_code'           => $clean($get($row, 'county_code')),

                'year'                  => $clean($get($row, 'Year')),
                'status'                => $clean($get($row, 'status')),

                'total_stock'           => $toInt($get($row, 'Total_stock')),
                'new_builds'            => $toInt($get($row, 'New_Builds')),
                'acquisitions'          => $toInt($get($row, 'Acquisitions')),

                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            // If the row is basically empty, skip it
            if (!$payload['lad23_code'] || !$payload['year'] || !$payload['status']) {
                continue;
            }

            $rows[] = $payload;
            $count++;

            if (count($rows) >= $chunkSize) {
                $inserted += $this->flush($rows);
                $rows = [];
            }

            if ($count % 500 === 0) {
                $bar->advance(500);
            }
        }

        // final flush
        if ($rows) {
            $inserted += $this->flush($rows);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Done. Parsed rows: {$count}, inserted/updated: {$inserted}");

        return self::SUCCESS;
    }

    /**
     * Upsert a chunk. Requires a unique index on (lad23_code, year, status).
     */
    private function flush(array $rows): int
    {
        // Upsert updates the numeric values and geography/name fields if re-run
        DB::table('england_council_housing_stock')->upsert(
            $rows,
            ['lad23_code', 'year', 'status'],
            [
                'local_authority',
                'local_authority_code',
                'lad23_name',
                'lad23_type',
                'region_name',
                'region_code',
                'county_name',
                'county_code',
                'total_stock',
                'new_builds',
                'acquisitions',
                'updated_at',
            ]
        );

        return count($rows);
    }
}
