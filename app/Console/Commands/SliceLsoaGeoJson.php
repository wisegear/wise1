<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SliceLsoaGeojson extends Command
{
    protected $signature = 'geo:slice-lsoa
                            {source : Path to the big LSOA GeoJSON file}
                            {--code-field=LSOA21CD : The property that contains the LSOA code}
                            {--prefix= : Optional code prefix filter (e.g. W01 for Wales)}';

    protected $description = 'Split a large LSOA GeoJSON into one file per LSOA code for Leaflet.';

    public function handle()
    {
        $source    = $this->argument('source');          // e.g. public/geo/lsoa/lsoa21_ew.geojson
        $codeField = $this->option('code-field') ?? 'LSOA21CD';
        $prefix    = $this->option('prefix');            // e.g. W01 (Wales only)

        if (!File::exists($source)) {
            $this->error("Source file not found: $source");
            return 1;
        }

        $this->info("Loading $source ...");

        // Read + decode the big GeoJSON
        $json = json_decode(File::get($source), true);

        // Basic validation
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

        $count  = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            // Make sure the feature has the code field
            if (!isset($feature['properties'][$codeField])) {
                $skipped++;
                continue;
            }

            $code = $feature['properties'][$codeField];

            // Optional prefix filter (e.g. "W01" for Wales, "S01" for Scotland)
            if ($prefix && !str_starts_with($code, $prefix)) {
                $skipped++;
                continue;
            }

            // Build a minimal single-feature FeatureCollection
            $single = [
                'type' => 'FeatureCollection',
                'features' => [
                    $feature,
                ],
            ];

            $outPath = $outDir . '/' . $code . '.geojson';
            File::put($outPath, json_encode($single));

            $count++;

            // Light progress output every 500 writes, just so big runs feel alive
            if ($count % 500 === 0) {
                $this->info("... wrote $count feature files so far");
            }
        }

        $this->info("✅ Done. Wrote $count feature files to $outDir");
        $this->info("⏭ Skipped $skipped features (no code field or prefix mismatch)");
        $this->info("Example file (if England style): $outDir/E01000001.geojson");

        return 0;
    }
}
