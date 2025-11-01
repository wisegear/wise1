<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * SliceWalesGeojson
 *
 * This command takes the Wales WIMD 2019 boundary GeoJSON
 * (LSOA 2011-ish codes, e.g. W01000396 in `lsoa_code`)
 * and writes one tiny GeoJSON per LSOA to public/geo/wales/sliced/.
 *
 * Usage:
 *   php artisan geo:slice-wales public/geo/wales/wind2019.json
 */
class SliceWalesGeojson extends Command
{
    protected $signature = 'geo:slice-wales
                            {source : Path to the Wales WIMD2019 GeoJSON file}';

    protected $description = 'Slice Wales WIMD2019 GeoJSON into per-LSOA files for Leaflet polygons.';

    public function handle()
    {
        $source = $this->argument('source'); // e.g. public/geo/wales/wind2019.json

        if (!File::exists($source)) {
            $this->error("Source file not found: $source");
            return 1;
        }

        $this->info("Loading $source ...");

        // Read + decode the big GeoJSON
        $json = json_decode(File::get($source), true);

        // We expect a FeatureCollection just like Scotland/England
        if (!is_array($json)) {
            $this->error('File is not valid JSON.');
            return 1;
        }

        // Some Welsh downloads wrap differently (e.g. {"type":"FeatureCollection","features":[...]})
        // We'll try to be flexible:
        if (($json['type'] ?? '') !== 'FeatureCollection' || !isset($json['features']) || !is_array($json['features'])) {
            // Some Welsh spatial portals export like:
            // { "features": [...], "crs": ..., "type": "FeatureCollection", ... }
            // If it’s *really* nonstandard we bail.
            if (!isset($json['features']) || !is_array($json['features'])) {
                $this->error('Source does not look like a FeatureCollection GeoJSON with a features[] array.');
                return 1;
            }
        }

        $features = $json['features'];

        // Output directory for Wales slices
        $outDir = public_path('geo/wales/sliced');
        if (!File::exists($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $count   = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            // We expect something like:
            // "properties": {
            //   "gid": 1,
            //   "lsoa_code": "W01000240",
            //   "lsoa_name": "...",
            //   ...
            // }
            if (
                !isset($feature['properties']) ||
                !isset($feature['properties']['lsoa_code'])
            ) {
                $skipped++;
                continue;
            }

            $code = $feature['properties']['lsoa_code'];
            if (!$code || !is_string($code)) {
                $skipped++;
                continue;
            }

            // Build a minimal single-feature FeatureCollection for Leaflet
            $single = [
                'type' => 'FeatureCollection',
                'features' => [
                    $feature,
                ],
            ];

            $outPath = $outDir . '/' . $code . '.geojson';
            File::put($outPath, json_encode($single));

            $count++;

            // Progress ping every ~200 files so big runs feel alive
            if ($count % 200 === 0) {
                $this->info("... wrote $count slices so far (latest $code)");
            }
        }

        $this->info("✅ Done. Wrote $count Wales LSOA shape files to $outDir");
        $this->info("⏭ Skipped $skipped features with no lsoa_code");

        $this->info("Example output (should now include older codes like W01000396): $outDir/W01000396.geojson");

        return 0;
    }
}
