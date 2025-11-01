<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SliceLsoaGeojson extends Command
{
    protected $signature = 'geo:slice-lsoa
                            {source : Path to the big LSOA/Data Zone GeoJSON file}
                            {--code-field=LSOA21CD : The property that contains the area code (e.g. LSOA21CD, DataZone)}
                            {--prefix= : Optional code prefix filter (e.g. W01 for Wales, S01 for Scotland)}';

    protected $description = 'Split a large GeoJSON (England LSOA / Scotland Data Zone / Wales LSOA) into one file per code for Leaflet.';

    public function handle()
    {
        $source    = $this->argument('source');          // e.g. public/geo/lsoa/lsoa21_ew.geojson OR public/geo/scotland/simd2020.geojson
        $codeField = $this->option('code-field') ?? 'LSOA21CD';
        $prefix    = $this->option('prefix');            // e.g. W01, S01

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

        /**
         * Decide output folder based on prefix (country).
         *
         * - England (no prefix, or not starting with W01/S01): public/geo/lsoa/sliced
         * - Wales   (W01…):                                    public/geo/wales/sliced
         * - Scotland(S01…):                                    public/geo/scotland/sliced
         *
         * This keeps each country's shapes separate so we don't
         * accidentally commit a giant mixed folder, and lets the blades
         * load e.g. `/geo/wales/sliced/{code}.geojson`.
         */
        $baseOutDir = 'geo/lsoa/sliced';

        if ($prefix) {
            if (str_starts_with($prefix, 'W01')) {
                $baseOutDir = 'geo/wales/sliced';
            } elseif (str_starts_with($prefix, 'S01')) {
                $baseOutDir = 'geo/scotland/sliced';
            }
        }

        $outDir = public_path($baseOutDir);

        if (!File::exists($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $count   = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            // Ensure the feature has the expected code field
            if (!isset($feature['properties'][$codeField])) {
                $skipped++;
                continue;
            }

            $code = $feature['properties'][$codeField];

            // Optional prefix filter (e.g. only W01* for Wales or S01* for Scotland)
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

            // Light progress output so big runs feel alive
            if ($count % 500 === 0) {
                $this->info("... wrote $count feature files so far");
            }
        }

        $this->info("✅ Done. Wrote $count feature files to $outDir");
        $this->info("⏭ Skipped $skipped features (no code field or prefix mismatch)");

        $this->info("Example England file:   public/geo/lsoa/sliced/E01000001.geojson");
        $this->info("Example Scotland file:  public/geo/scotland/sliced/S01006506.geojson");
        $this->info("Example Wales file:     public/geo/wales/sliced/W01000001.geojson");

        return 0;
    }
}
