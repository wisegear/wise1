<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SliceLsoaGeojson extends Command
{
    protected $signature = 'geo:slice-lsoa
                            {source : Path to the big LSOA GeoJSON file}
                            {--code-field=LSOA21CD : The property that contains the LSOA code}';

    protected $description = 'Split a large LSOA GeoJSON into one file per LSOA code for Leaflet.';

    public function handle()
    {
        $source    = $this->argument('source');          // e.g. public/geo/lsoa/lsoa21_ew.json
        $codeField = $this->option('code-field');        // e.g. LSOA21CD

        if (!File::exists($source)) {
            $this->error("Source file not found: $source");
            return 1;
        }

        $this->info("Loading $source ...");

        // Read + decode the big GeoJSON
        $json = json_decode(File::get($source), true);

        if (!is_array($json) || ($json['type'] ?? '') !== 'FeatureCollection') {
            $this->error('Source is not a valid FeatureCollection GeoJSON.');
            return 1;
        }

        $features = $json['features'] ?? [];
        if (!is_array($features) || count($features) === 0) {
            $this->error('No features found in GeoJSON.');
            return 1;
        }

        // Output directory for individual files
        $outDir = public_path('geo/lsoa/sliced');
        if (!File::exists($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $count = 0;

        foreach ($features as $feature) {
            if (!isset($feature['properties'][$codeField])) {
                continue;
            }

            $code = $feature['properties'][$codeField];

            // Minimal single-feature FeatureCollection
            $single = [
                'type' => 'FeatureCollection',
                'features' => [
                    $feature,
                ],
            ];

            $outPath = $outDir . '/' . $code . '.geojson';

            File::put($outPath, json_encode($single));

            $count++;
        }

        $this->info("Done. Wrote $count features to $outDir");
        $this->info("Example file: $outDir/E01000001.geojson");

        return 0;
    }
}
