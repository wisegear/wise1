<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportNiAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan ni:export-areas
     */
    protected $signature = 'ni:export-areas';

    /**
     * The console command description.
     */
    protected $description = 'Export Northern Ireland deprivation Small Areas to a JSON file for the dashboard search.';

    public function handle(): int
    {
        $this->info('Exporting NI areas from ni_deprivation…');

        // Pull all Small Areas with basic descriptive info
        $rows = DB::table('ni_deprivation')
            ->select([
                'SA2011',
                'SOA2001name',
                'LGD2014name',
                'UR2015',
            ])
            ->orderBy('LGD2014name')
            ->orderBy('SOA2001name')
            ->orderBy('SA2011')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found in ni_deprivation – nothing to export.');
            return self::SUCCESS;
        }

        // Map into a lightweight structure for the frontend
        $areas = $rows->map(function ($row) {
            // Build a readable label like "SOA name — Council"
            $parts = [];
            if (!empty($row->SOA2001name)) {
                $parts[] = $row->SOA2001name;
            }
            if (!empty($row->LGD2014name)) {
                $parts[] = $row->LGD2014name;
            }

            $label = implode(' — ', $parts);
            if ($label === '') {
                $label = $row->SA2011; // fallback
            }

            return [
                'sa'      => $row->SA2011,
                'name'    => $row->SOA2001name ?? null,
                'council' => $row->LGD2014name ?? null,
                'ur2015'  => $row->UR2015 ?? null,
                'label'   => $label,
                // Use your existing route pattern: /deprivation/northern-ireland/{sa}
                'path'    => '/deprivation/northern-ireland/' . $row->SA2011,
            ];
        })->values()->all();

        // Ensure the target directory exists: public/data/ni_areas.json
        $dir = public_path('data');
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                $this->error('Failed to create directory: ' . $dir);
                return self::FAILURE;
            }
        }

        $file = $dir . '/ni_areas.json';

        file_put_contents(
            $file,
            json_encode($areas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->info('Exported ' . count($areas) . ' NI areas to: ' . $file);

        return self::SUCCESS;
    }
}
