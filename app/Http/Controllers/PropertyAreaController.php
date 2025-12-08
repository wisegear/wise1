<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PropertyAreaController extends Controller
{
    public function show(string $type, string $slug)
    {
        $type = strtolower($type);

        $allowedTypes = ['locality', 'town', 'district', 'county'];
        if (! in_array($type, $allowedTypes, true)) {
            abort(404);
        }

        $jsonPath = public_path('data/property_districts.json');
        if (! file_exists($jsonPath)) {
            abort(404, 'Area index not available.');
        }

        $areas = json_decode(file_get_contents($jsonPath), true) ?? [];

        // Find the area whose type + slug matches the URL
        $area = collect($areas)->first(function ($item) use ($type, $slug) {
            if (!is_array($item)) return false;
            if (($item['type'] ?? null) !== $type) return false;

            $name = $item['name'] ?? $item['label'] ?? null;
            if (! $name) return false;

            return Str::slug($name) === $slug;
        });

        if (! $area) {
            abort(404);
        }

        $areaName = $area['name'] ?? $area['label'];

        // Map the logical type to the actual column in land_registry
        $columnMap = [
            'locality' => 'Locality',
            'town'     => 'TownCity',
            'district' => 'District',
            'county'   => 'County',
        ];

        $column = $columnMap[$type];

        // High-level summary (MySQL)
        $summary = DB::table('land_registry')
            ->selectRaw('
                COUNT(*)      as sales_count,
                MIN(`Price`)  as min_price,
                MAX(`Price`)  as max_price,
                AVG(`Price`)  as avg_price
            ')
            ->where($column, $areaName)
            ->where('PPDCategoryType', '<>', 'B')
            ->first();

        // Simple yearly series for charts later (MySQL)
        $byYear = DB::table('land_registry')
            ->selectRaw('
                YEAR(`Date`)      as year,
                COUNT(*)          as sales_count,
                AVG(`Price`)      as avg_price
            ')
            ->where($column, $areaName)
            ->where('PPDCategoryType', '<>', 'B')
            ->groupBy(DB::raw('YEAR(`Date`)'))
            ->orderBy('year')
            ->get();

        // Yearly series by property type (Detached, Semi, Terraced, Flat)
        $propertyTypes = [
            'D' => ['key' => 'detached',  'label' => 'Detached'],
            'S' => ['key' => 'semi',      'label' => 'Semi-detached'],
            'T' => ['key' => 'terraced',  'label' => 'Terraced'],
            'F' => ['key' => 'flat',      'label' => 'Flat'],
        ];

        $byType = [];

        foreach ($propertyTypes as $code => $meta) {
            $series = DB::table('land_registry')
                ->selectRaw('
                    YEAR(`Date`)      as year,
                    COUNT(*)          as sales_count,
                    AVG(`Price`)      as avg_price
                ')
                ->where($column, $areaName)
                ->where('PPDCategoryType', '<>', 'B')
                ->where('PropertyType', $code)
                ->groupBy(DB::raw('YEAR(`Date`)'))
                ->orderBy('year')
                ->get();

            $byType[$meta['key']] = [
                'label' => $meta['label'],
                'series' => $series,
            ];
        }

        // Build a unified yearly axis from the overall byYear series
        $years = $byYear->pluck('year')->map(fn($y) => (int) $y)->values();
        $yearIndex = [];
        foreach ($years as $i => $y) {
            $yearIndex[$y] = $i;
        }

        // 1) Yearly split of sales by property type (using existing $byType series)
        $propertyTypeSplit = [
            'years' => $years,
            'types' => [],
        ];

        foreach ($byType as $key => $meta) {
            $series = $meta['series'];
            $counts = array_fill(0, $years->count(), 0);

            foreach ($series as $row) {
                $year = (int) $row->year;
                if (! isset($yearIndex[$year])) {
                    continue;
                }
                $idx = $yearIndex[$year];
                $counts[$idx] = (int) $row->sales_count;
            }

            $propertyTypeSplit['types'][$key] = [
                'label'  => $meta['label'],
                'counts' => $counts,
            ];
        }

        // 2) Yearly split of sales by NewBuild flag (Y = new build, N = existing)
        $newBuildRaw = DB::table('land_registry')
            ->selectRaw('
                YEAR(`Date`)      as year,
                NewBuild          as new_build,
                COUNT(*)          as sales_count
            ')
            ->where($column, $areaName)
            ->where('PPDCategoryType', '<>', 'B')
            ->whereIn('NewBuild', ['Y', 'N'])
            ->groupBy(DB::raw('YEAR(`Date`)'), 'NewBuild')
            ->orderBy('year')
            ->get();

        $newBuildSplit = [
            'years'  => $years,
            'series' => [
                'Y' => [
                    'label'  => 'New build',
                    'counts' => array_fill(0, $years->count(), 0),
                ],
                'N' => [
                    'label'  => 'Existing',
                    'counts' => array_fill(0, $years->count(), 0),
                ],
            ],
        ];

        foreach ($newBuildRaw as $row) {
            $year = (int) $row->year;
            if (! isset($yearIndex[$year])) {
                continue;
            }

            $flag = ($row->new_build === 'Y') ? 'Y' : 'N';
            if (! isset($newBuildSplit['series'][$flag])) {
                continue;
            }

            $idx = $yearIndex[$year];
            $newBuildSplit['series'][$flag]['counts'][$idx] = (int) $row->sales_count;
        }

        return view('property.area-show', [
            'type'              => $type,
            'areaName'          => $areaName,
            'summary'           => $summary,
            'byYear'            => $byYear,
            'byType'            => $byType,
            'propertyTypeSplit' => $propertyTypeSplit,
            'newBuildSplit'     => $newBuildSplit,
            'column'            => $column,
        ]);
    }
}
