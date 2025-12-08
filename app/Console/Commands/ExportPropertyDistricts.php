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
    protected $description = 'Export unique property areas (locality/town/district/county) with de-duplicated names to a JSON file.';

    public function handle(): int
    {
        $this->info('Exporting areas from land_registry…');

        // Pull distinct combinations of the 4 area columns.
        $rows = DB::table('land_registry')
            ->select('Locality', 'TownCity', 'District', 'County')
            ->groupBy('Locality', 'TownCity', 'District', 'County')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found in land_registry – nothing to export.');
            return self::SUCCESS;
        }

        /**
         * Priority for levels:
         *  locality (1) < town (2) < district (3) < county (4)
         *
         * For any given *name* (e.g. "BLACKPOOL") we keep it only at the
         * highest level it ever appears. So if BLACKPOOL appears as:
         *  - locality
         *  - town
         *  - district
         *  - county
         * we only export: type = county, name = BLACKPOOL.
         */
        $levels = [
            'locality' => 1,
            'town'     => 2,
            'district' => 3,
            'county'   => 4,
        ];

        // nameKey (lowercased) => ['type' => 'county', 'name' => 'BLACKPOOL']
        $bestByName = [];

        foreach ($rows as $row) {
            $locality = trim((string) ($row->Locality ?? ''));
            $town     = trim((string) ($row->TownCity ?? ''));
            $district = trim((string) ($row->District ?? ''));
            $county   = trim((string) ($row->County ?? ''));

            $candidates = [];

            if ($locality !== '') {
                $candidates[] = ['type' => 'locality', 'name' => $locality];
            }
            if ($town !== '') {
                $candidates[] = ['type' => 'town', 'name' => $town];
            }
            if ($district !== '') {
                $candidates[] = ['type' => 'district', 'name' => $district];
            }
            if ($county !== '') {
                $candidates[] = ['type' => 'county', 'name' => $county];
            }

            foreach ($candidates as $candidate) {
                $type = $candidate['type'];
                $name = $candidate['name'];

                // Use lowercase key so BLACKPOOL / Blackpool / blackpool collapse.
                $key = mb_strtolower($name);

                $level = $levels[$type] ?? 0;
                $currentLevel = isset($bestByName[$key])
                    ? ($levels[$bestByName[$key]['type']] ?? 0)
                    : 0;

                // If we've never seen this name OR this is a higher level, replace it.
                if ($level > $currentLevel) {
                    $bestByName[$key] = [
                        'type' => $type,
                        'name' => $name,
                    ];
                }
            }
        }

        // Build final flat list for JSON.
        $areas = [];

        foreach ($bestByName as $entry) {
            $type = $entry['type'];
            $name = $entry['name'];

            $areas[] = [
                'type'  => $type,
                'name'  => $name,
                'label' => $name . ' (' . ucfirst($type) . ')',
                'path'  => '/property/area/' . $type . '/' . Str::slug($name),
            ];
        }

        if (empty($areas)) {
            $this->warn('No areas were derived – nothing to export.');
            return self::SUCCESS;
        }

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
            json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Exported ' . count($areas) . ' areas to: ' . $file);

        return self::SUCCESS;
    }
}