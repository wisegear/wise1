<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportScottishHousingStock extends Command
{
    protected $signature = 'housing:import-scotland {file}';
    protected $description = 'Import Scottish local authority housing stock from Excel';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return self::FAILURE;
        }

        $this->info('Loading spreadsheet...');
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Remove header row
        array_shift($rows);

        DB::disableQueryLog();
        DB::table('scottish_housing_stock')->truncate();

        $insert = [];

        foreach ($rows as $row) {
            // Skip empty rows
            if (empty($row[0]) || empty($row[1])) {
                continue;
            }

            $insert[] = [
                'year' => (int) $row[0],
                'council' => trim($row[1]),

                'total_stock' => $this->num($row[2]),

                'house' => $this->num($row[3]),
                'all_flats' => $this->num($row[4]),
                'high_rise_flat' => $this->num($row[5]),
                'tenement' => $this->num($row[6]),
                'four_in_a_block' => $this->num($row[7]),
                'other_flat' => $this->num($row[8]),
                'property_type_unknown' => $this->num($row[9]),

                'pre_1919' => $this->num($row[10]),
                'y1919_44' => $this->num($row[11]),
                'y1945_64' => $this->num($row[12]),
                'y1965_1982' => $this->num($row[13]),
                'post_1982' => $this->num($row[14]),
                'build_period_unknown' => $this->num($row[15]),

                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Chunked insert (safe even if this grows later)
        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('scottish_housing_stock')->insert($chunk);
        }

        $this->info('Import complete: ' . count($insert) . ' rows inserted.');

        return self::SUCCESS;
    }

    /**
     * Convert spreadsheet values to int (dashes/blanks become 0)
     */
    private function num($value): int
    {
        if ($value === null) {
            return 0;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '-') {
            return 0;
        }

        return (int) str_replace(',', '', $value);
    }
}
