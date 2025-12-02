<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExportPropertyDistricts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan property:export-districts
     */
    protected $signature = 'property:export-districts';

    /**
     * The console command description.
     */
    protected $description = 'Export unique Land Registry districts to a JSON file for the property search.';

    public function handle(): int
    {
        $this->info('Exporting districts from land_registry…');

        // Adjust column names here if needed (district / county / town)
        $rows = DB::table('land_registry')
            ->select([
                'District',
            ])
            ->whereNotNull('District')
            ->groupBy('District')
            ->orderBy('District')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found in land_registry – nothing to export.');
            return self::SUCCESS;
        }

        // Map into a lightweight structure for the frontend
        $districts = $rows->map(function ($row) {
            return [
                'District' => $row->District,
                'label'    => $row->District,
                'path'     => '/property/district/' . Str::slug($row->District),
            ];
        })->values()->all();

        // Ensure the target directory exists, e.g. public/data/property_districts.json
        $dir = public_path('data');
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                $this->error('Failed to create directory: ' . $dir);
                return self::FAILURE;
            }
        }

        $file = $dir . '/property_districts.json';

        file_put_contents(
            $file,
            json_encode($districts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Exported ' . count($districts) . ' districts to: ' . $file);

        return self::SUCCESS;
    }
}
