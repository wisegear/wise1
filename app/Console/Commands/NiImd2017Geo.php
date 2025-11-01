<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class NiImd2017Geo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo:slice-ni {source?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Slice NI SA2011 GeoJSON (NIMDM areas) into individual Small Area .geojson files for Leaflet.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Resolve source path
        // Default: public/geo/northern_ireland/SA2011_full.json
        $arg = $this->argument('source');
        $relativeSource = $arg ?: 'public/geo/northern_ireland/SA2011_full.json';
        $sourcePath = base_path($relativeSource);

        // 2. Confirm source exists
        if (!File::exists($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");
            return Command::FAILURE;
        }

        // 3. Read and decode the full NI Small Areas GeoJSON
        $this->info('Reading source GeoJSON...');
        $raw = File::get($sourcePath);
        $json = json_decode($raw, true);

        if (!is_array($json) || ($json['type'] ?? null) !== 'FeatureCollection') {
            $this->error('Source is not a valid FeatureCollection GeoJSON.');
            return Command::FAILURE;
        }

        $features = $json['features'] ?? [];
        $countTotal = count($features);
        if ($countTotal === 0) {
            $this->error('No features found in source.');
            return Command::FAILURE;
        }

        // 4. Prepare output directory (NI has its own sliced folder)
        $outputDir = base_path('public/geo/northern_ireland/sliced');
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0775, true);
        }

        $this->info("Slicing {$countTotal} features to {$outputDir} ...");
        $this->info('Output pattern: /public/geo/northern_ireland/sliced/{SA2011}.geojson');

        $written = 0;
        $skipped = 0;

        foreach ($features as $idx => $feature) {
            $props = $feature['properties'] ?? [];

            // We expect the Small Area code in SA2011 (e.g. N00001467)
            $code = $props['SA2011']
                ?? $props['sa2011']
                ?? $props['Sa2011']
                ?? null;

            if (!$code) {
                $skipped++;
                continue; // Cannot name file without a code
            }

            $code = trim($code);

            // Build a single-feature FeatureCollection for Leaflet consumption
            $single = [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => $feature['geometry'] ?? null,
                        'properties' => [
                            'SA2011'         => $code,
                            'SOA2001name'    => $props['SOA2001name']    ?? null,
                            'LGD2014name'    => $props['LGD2014name']    ?? null,
                            'LGD2014dcode'   => $props['LGD2014dcode']   ?? null,
                            'UR2015'         => $props['UR2015']         ?? null,
                        ],
                    ],
                ],
            ];

            $outPath = $outputDir . DIRECTORY_SEPARATOR . $code . '.geojson';

            // Write file (pretty JSON for debugging / git diffs)
            File::put(
                $outPath,
                json_encode(
                    $single,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
            $written++;

            // Light progress output every 100 features so we see it's alive
            if ($written % 100 === 0) {
                $this->info("Written {$written}/{$countTotal} ...");
            }
        }

        $this->info("Done. Wrote {$written} feature files. Skipped {$skipped} with no SA2011 code.");
        $this->info('Each file can now be fetched by Leaflet at /geo/northern_ireland/sliced/{SA2011}.geojson');

        return Command::SUCCESS;
    }
}
