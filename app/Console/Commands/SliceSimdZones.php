<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SliceSimdZones extends Command
{
    protected $signature = 'geo:slice-simd 
        {source : path to full SIMD GeoJSON (e.g. public/geo/scotland/simd2020_all.geojson)} 
        {--code-field=DataZone : property name that holds the zone code} 
        {--out=public/geo/scotland/sliced : output folder for individual features}';

    protected $description = 'Split the full Scotland SIMD GeoJSON into one file per DataZone (for Leaflet).';

    public function handle()
    {
        $srcPath   = $this->argument('source');
        $codeField = $this->option('code-field');
        $outDir    = $this->option('out');

        if (!file_exists($srcPath)) {
            $this->error("Source file not found: {$srcPath}");
            return Command::FAILURE;
        }

        if (!is_dir($outDir)) {
            if (!mkdir($outDir, 0775, true) && !is_dir($outDir)) {
                $this->error("Could not create output directory: {$outDir}");
                return Command::FAILURE;
            }
        }

        $json = json_decode(file_get_contents($srcPath), true);

        if (!$json || !isset($json['features']) || !is_array($json['features'])) {
            $this->error("Source does not look like a FeatureCollection with features[]");
            return Command::FAILURE;
        }

        $count = 0;

        foreach ($json['features'] as $feature) {
            if (!isset($feature['properties'][$codeField])) {
                // skip anything without a DataZone code
                continue;
            }

            $code = strtoupper(trim($feature['properties'][$codeField])); // e.g. S01006506

            // Build a tiny FeatureCollection containing just this one feature
            $single = [
                'type' => 'FeatureCollection',
                'features' => [
                    $feature,
                ],
            ];

            $outPath = rtrim($outDir, '/').'/'.$code.'.geojson';

            file_put_contents(
                $outPath,
                json_encode($single, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $count++;
        }

        $this->info("Done. Wrote {$count} individual zone files to {$outDir}");
        return Command::SUCCESS;
    }
}