<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Slice Wales WIMD 2019 master GeoJSON into individual per-LSOA files
 * AND reproject coordinates from British National Grid (EPSG:27700)
 * to WGS84 lon/lat so Leaflet can render them directly.
 *
 * Usage:
 *   php artisan geo:slice-wales
 *
 * Expects source file:
 *   public/geo/wales/wimd2019.json
 *
 * Outputs:
 *   public/geo/lsoa/sliced/W01000xxxx.geojson
 */
class SliceWalesGeo extends Command
{
    protected $signature = 'geo:slice-wales';
    protected $description = 'Slice Wales WIMD geojson into per-LSOA WGS84 files for Leaflet';

    public function handle(): int
    {
        $source = public_path('geo/wales/wimd2019.json');
        $outDir = public_path('geo/wales/sliced');

        if (!File::exists($source)) {
            $this->error("Source not found: {$source}");
            return 1;
        }

        if (!File::exists($outDir)) {
            File::makeDirectory($outDir, 0755, true);
        }

        $raw = File::get($source);
        $json = json_decode($raw, true);

        if (!$json || !isset($json['features']) || !is_array($json['features'])) {
            $this->error('Invalid GeoJSON structure (no features array)');
            return 1;
        }

        $count = 0;

        foreach ($json['features'] as $feature) {
            // We expect properties.lsoa_code like "W01000396"
            $props = $feature['properties'] ?? [];
            $code  = $props['lsoa_code'] ?? null;

            if (!$code || !preg_match('/^W01\d+$/', $code)) {
                // skip weird / non-Wales entries
                continue;
            }

            // Reproject this geometry from EPSG:27700 -> WGS84
            $geom = $feature['geometry'] ?? null;
            if (!$geom || !isset($geom['type']) || !isset($geom['coordinates'])) {
                $this->warn("Skipping {$code}: no geometry");
                continue;
            }

            $reprojectedGeometry = $this->reprojectGeometryToWGS84($geom);

            // Build a mini FeatureCollection (Leaflet friendly)
            $out = [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type'       => 'Feature',
                        'geometry'   => $reprojectedGeometry,
                        'properties' => $props,
                    ],
                ],
            ];

            $destPath = $outDir . DIRECTORY_SEPARATOR . $code . '.geojson';

            File::put($destPath, json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

            $count++;
        }

        $this->info("Done. Wrote {$count} Wales LSOA files to {$outDir}");
        $this->info('You can now git add/commit/push those new/updated W01000xxx.geojson files.');

        return 0;
    }

    /**
     * Reproject an entire geometry (Polygon or MultiPolygon)
     * from British National Grid EPSG:27700 (easting, northing in metres)
     * to WGS84 lon/lat in degrees for Leaflet.
     */
    protected function reprojectGeometryToWGS84(array $geom): array
    {
        $type = $geom['type'];
        $coords = $geom['coordinates'];

        if ($type === 'Polygon') {
            // coords: [ [ [E,N], [E,N], ... ] (ring1), [ ... ] (holes?) ]
            $newPoly = [];
            foreach ($coords as $ring) {
                $newRing = [];
                foreach ($ring as $pair) {
                    [$E, $N] = $pair;
                    [$lon, $lat] = $this->bngToWgs84($E, $N);
                    $newRing[] = [$lon, $lat];
                }
                $newPoly[] = $newRing;
            }
            return [
                'type' => 'Polygon',
                'coordinates' => $newPoly,
            ];
        }

        if ($type === 'MultiPolygon') {
            // coords: [ [ [ [E,N], ... ] (ring1), [ ... ] (holes?) ], ... ]
            $newMulti = [];
            foreach ($coords as $poly) {
                $newPoly = [];
                foreach ($poly as $ring) {
                    $newRing = [];
                    foreach ($ring as $pair) {
                        [$E, $N] = $pair;
                        [$lon, $lat] = $this->bngToWgs84($E, $N);
                        $newRing[] = [$lon, $lat];
                    }
                    $newPoly[] = $newRing;
                }
                $newMulti[] = $newPoly;
            }
            return [
                'type' => 'MultiPolygon',
                'coordinates' => $newMulti,
            ];
        }

        // fallback: just return original if we hit a geom type we didn't handle
        return $geom;
    }

    /**
     * Convert British National Grid Easting/Northing (EPSG:27700)
     * -> WGS84 lon/lat.
     *
     * Steps:
     *   1. EPSG:27700 (OSGB36 / Airy 1830) EN -> lat/lon (OSGB36)
     *   2. Helmert transform OSGB36 lat/lon -> WGS84 lat/lon
     *
     * This is a standard Ordnance Survey formula.
     */
    protected function bngToWgs84(float $E, float $N): array
    {
        // First convert Easting/Northing to OSGB36 lat/lon (deg)
        [$latOsgbDeg, $lonOsgbDeg] = $this->enToOsgb36LatLon($E, $N);

        // Then convert OSGB36 lat/lon -> WGS84 lat/lon
        [$latWgsDeg, $lonWgsDeg] = $this->osgb36ToWgs84($latOsgbDeg, $lonOsgbDeg);

        // Leaflet wants [lon, lat]
        return [$lonWgsDeg, $latWgsDeg];
    }

    /**
     * Convert Easting/Northing (EPSG:27700) to OSGB36 lat/lon in degrees.
     * Based on Ordnance Survey formulas.
     */
    protected function enToOsgb36LatLon(float $E, float $N): array
    {
        // Airy 1830 ellipsoid and BNG params
        $a  = 6377563.396;
        $b  = 6356256.909;
        $F0 = 0.9996012717;
        $lat0 = deg2rad(49.0);     // True origin lat
        $lon0 = deg2rad(-2.0);     // True origin lon
        $N0 = -100000.0;           // Northing of true origin
        $E0 =  400000.0;           // Easting of true origin
        $e2 = 1 - ($b * $b) / ($a * $a);
        $n  = ($a - $b) / ($a + $b);

        // Initial lat estimate
        $lat = $lat0;
        $M = 0.0;

        do {
            $latPrev = $lat;
            $Ma = (1 + $n + (5.0/4.0)*$n*$n + (5.0/4.0)*$n*$n*$n) * ($latPrev - $lat0);
            $Mb = (3*$n + 3*$n*$n + (21.0/8.0)*$n*$n*$n) * sin($latPrev - $lat0) * cos($latPrev + $lat0);
            $Mc = ((15.0/8.0)*$n*$n + (15.0/8.0)*$n*$n*$n) * sin(2*($latPrev - $lat0)) * cos(2*($latPrev + $lat0));
            $Md = (35.0/24.0)*$n*$n*$n * sin(3*($latPrev - $lat0)) * cos(3*($latPrev + $lat0));

            $M = $b*$F0 * ($Ma - $Mb + $Mc - $Md);

            $lat = ($N - $N0 - $M)/($a*$F0) + $latPrev;
        } while (abs($N - $N0 - $M) >= 0.00001); // iterate until mm-level

        // Now compute nu, rho, eta2
        $sinLat = sin($lat);
        $cosLat = cos($lat);
        $nu = $a*$F0 / sqrt(1 - $e2*$sinLat*$sinLat);
        $rho = $a*$F0*(1 - $e2) / pow(1 - $e2*$sinLat*$sinLat, 1.5);
        $eta2 = $nu/$rho - 1.0;

        $tanLat = tan($lat);
        $tan2 = $tanLat*$tanLat;
        $tan4 = $tan2*$tan2;
        $tan6 = $tan4*$tan2;
        $secLat = 1.0/$cosLat;

        $dE = ($E - $E0);

        // Series expansion for latitude
        $VII  = $tanLat/(2*$rho*$nu);
        $VIII = $tanLat/(24*$rho*pow($nu,3))*(5+3*$tan2+$eta2-9*$tan2*$eta2);
        $IX   = $tanLat/(720*$rho*pow($nu,5))*(61+90*$tan2+45*$tan4);

        $latRad = $lat - $dE*$dE*$VII
                       + pow($dE,4)*$VIII
                       - pow($dE,6)*$IX;

        // Series expansion for longitude
        $X   = $secLat/$nu;
        $XI  = $secLat/(6*pow($nu,3))*($nu/$rho + 2*$tan2);
        $XII = $secLat/(120*pow($nu,5))*(5+28*$tan2+24*$tan4);
        $XIIA= $secLat/(5040*pow($nu,7))*(61+662*$tan2+1320*$tan4+720*$tan6);

        $lonRad = $lon0 + $dE*$X
                        - pow($dE,3)*$XI
                        + pow($dE,5)*$XII
                        - pow($dE,7)*$XIIA;

        return [ rad2deg($latRad), rad2deg($lonRad) ];
    }

    /**
     * Convert OSGB36 lat/lon (deg on Airy1830) to WGS84 lat/lon (deg on WGS84)
     * using a Helmert transform.
     */
    protected function osgb36ToWgs84(float $latDeg, float $lonDeg): array
    {
        // Ellipsoid params
        $aAiry = 6377563.396;
        $bAiry = 6356256.909;

        $aWGS  = 6378137.000;
        $bWGS  = 6356752.3141;

        // Convert to radians
        $lat = deg2rad($latDeg);
        $lon = deg2rad($lonDeg);

        // Height above ellipsoid – assume 0 for surface
        $H = 0.0;

        // Eccentricity squared
        $e2Airy = 1 - ($bAiry*$bAiry)/($aAiry*$aAiry);

        $sinLat = sin($lat);
        $cosLat = cos($lat);
        $sinLon = sin($lon);
        $cosLon = cos($lon);

        $nu = $aAiry / sqrt(1 - $e2Airy*$sinLat*$sinLat);

        // OSGB36 cartesian coords
        $X1 = ($nu + $H) * $cosLat * $cosLon;
        $Y1 = ($nu + $H) * $cosLat * $sinLon;
        $Z1 = (($bAiry*$bAiry)/($aAiry*$aAiry) * $nu + $H) * $sinLat;

        // Helmert transform params to WGS84
        // (in metres / arcseconds / ppm)
        $tx = 446.448;
        $ty = -125.157;
        $tz = 542.060;
        $rxSec = 0.1502;
        $rySec = 0.2470;
        $rzSec = 0.8421;
        $sPpm  = 20.4894;

        // Convert rotations to radians, scale to factor
        $rx = deg2rad($rxSec / 3600.0);
        $ry = deg2rad($rySec / 3600.0);
        $rz = deg2rad($rzSec / 3600.0);
        $s  = $sPpm * 1e-6 + 1.0;

        // Apply Helmert transform
        $X2 = $tx + $s*($X1 + (-$rz)*$Y1 + ($ry)*$Z1);
        $Y2 = $ty + $s*(($rz)*$X1 + $Y1 + (-$rx)*$Z1);
        $Z2 = $tz + $s*((-$ry)*$X1 + ($rx)*$Y1 + $Z1);

        // Now convert cartesian (WGS84) back to lat/lon
        $e2WGS = 1 - ($bWGS*$bWGS)/($aWGS*$aWGS);
        $p = sqrt($X2*$X2 + $Y2*$Y2);

        $latW = atan2($Z2, $p*(1-$e2WGS));
        // iterate to refine lat
        for ($i=0; $i<5; $i++) {
            $nuW = $aWGS / sqrt(1 - $e2WGS*sin($latW)*sin($latW));
            $latW = atan2($Z2 + $e2WGS*$nuW*sin($latW), $p);
        }

        $lonW = atan2($Y2, $X2);

        return [ rad2deg($latW), rad2deg($lonW) ];
    }
}
