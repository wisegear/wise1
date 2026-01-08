<?php

namespace App\Http\Controllers;

use App\Models\ScottishHousingStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocalAuthorityController extends Controller
{
    /**
     * Scotland: Local authority owned housing stock (2000+).
     * Prepares:
     * - National totals by year (for lightweight charts)
     * - Per-council series (for a council selector on the page)
     * - Top movers: biggest declines / increases in total stock between 2000 and 2025
     */
    public function scotland(Request $request)
    {
        // Load the dataset (small: ~833 rows)
        $rows = ScottishHousingStock::query()
            ->select([
                'year',
                'council',
                'total_stock',
                'house',
                'high_rise_flat',
                'tenement',
                'four_in_a_block',
                'other_flat',
                'property_type_unknown',
                'pre_1919',
                'y1919_44',
                'y1945_64',
                'y1965_1982',
                'post_1982',
                'build_period_unknown',
            ])
            ->orderBy('year')
            ->orderBy('council')
            ->get();

        // Compare window (used for filtering + top movers)
        $baselineYear = 2000;
        $compareYear = 2025;

        // Councils to include: those with non-zero total stock in the compare year
        $councils = ScottishHousingStock::query()
            ->where('year', $compareYear)
            ->where('total_stock', '>', 0)
            ->pluck('council')
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Restrict the in-memory rows to included councils only
        $rowsActive = $rows->whereIn('council', $councils);

        $years = $rowsActive->pluck('year')->unique()->sort()->values()->all();

        // Build per-council time series (for a dropdown selector in the UI)
        // Structure: [CouncilName => [Year => [metrics...]]]
        $byCouncil = [];
        foreach ($rowsActive as $r) {
            $byCouncil[$r->council][$r->year] = [
                'total_stock' => (int) $r->total_stock,

                // Types
                'house' => (int) $r->house,
                'high_rise_flat' => (int) $r->high_rise_flat,
                'tenement' => (int) $r->tenement,
                'four_in_a_block' => (int) $r->four_in_a_block,
                'other_flat' => (int) $r->other_flat,
                'property_type_unknown' => (int) $r->property_type_unknown,

                // Ages
                'pre_1919' => (int) $r->pre_1919,
                'y1919_44' => (int) $r->y1919_44,
                'y1945_64' => (int) $r->y1945_64,
                'y1965_1982' => (int) $r->y1965_1982,
                'post_1982' => (int) $r->post_1982,
                'build_period_unknown' => (int) $r->build_period_unknown,
            ];
        }

        // National totals by year (sum across councils) – useful for a quick overview chart
        $national = [];
        foreach ($years as $year) {
            $subset = $rowsActive->where('year', $year);

            $national[$year] = [
                'total_stock' => (int) $subset->sum('total_stock'),

                // Types
                'house' => (int) $subset->sum('house'),
                'high_rise_flat' => (int) $subset->sum('high_rise_flat'),
                'tenement' => (int) $subset->sum('tenement'),
                'four_in_a_block' => (int) $subset->sum('four_in_a_block'),
                'other_flat' => (int) $subset->sum('other_flat'),
                'property_type_unknown' => (int) $subset->sum('property_type_unknown'),

                // Ages
                'pre_1919' => (int) $subset->sum('pre_1919'),
                'y1919_44' => (int) $subset->sum('y1919_44'),
                'y1945_64' => (int) $subset->sum('y1945_64'),
                'y1965_1982' => (int) $subset->sum('y1965_1982'),
                'post_1982' => (int) $subset->sum('post_1982'),
                'build_period_unknown' => (int) $subset->sum('build_period_unknown'),
            ];
        }

        $movers = [];
        foreach ($councils as $council) {
            $y0 = $byCouncil[$council][$baselineYear]['total_stock'] ?? null;
            $y1 = $byCouncil[$council][$compareYear]['total_stock'] ?? null;

            if ($y0 === null || $y1 === null) {
                continue;
            }

            $delta = $y1 - $y0;
            $pct = ($y0 > 0) ? round(($delta / $y0) * 100, 1) : null;

            $movers[] = [
                'council' => $council,
                'year_2000' => $y0,
                'year_2025' => $y1,
                'delta' => $delta,
                'pct' => $pct,
            ];
        }

        $moversCollection = collect($movers);

        // Only true declines (negative deltas)
        $biggestDeclines = $moversCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) < 0)
            ->sortBy('delta')
            ->take(10)
            ->values()
            ->all();

        // Only true increases (positive deltas)
        $biggestIncreases = $moversCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) > 0)
            ->sortByDesc('delta')
            ->take(10)
            ->values()
            ->all();

        // Largest changes for a more recent window (2020 → 2025)
        $baselineYearRecent = 2020;

        $moversRecent = [];
        foreach ($councils as $council) {
            $y0 = $byCouncil[$council][$baselineYearRecent]['total_stock'] ?? null;
            $y1 = $byCouncil[$council][$compareYear]['total_stock'] ?? null;

            if ($y0 === null || $y1 === null) {
                continue;
            }

            $delta = $y1 - $y0;
            $pct = ($y0 > 0) ? round(($delta / $y0) * 100, 1) : null;

            $moversRecent[] = [
                'council' => $council,
                'year_2020' => $y0,
                'year_2025' => $y1,
                'delta' => $delta,
                'pct' => $pct,
            ];
        }

        $moversRecentCollection = collect($moversRecent);

        $biggestDeclines2020_2025 = $moversRecentCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) < 0)
            ->sortBy('delta')
            ->take(10)
            ->values()
            ->all();

        $biggestIncreases2020_2025 = $moversRecentCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) > 0)
            ->sortByDesc('delta')
            ->take(10)
            ->values()
            ->all();

        return view('local_authority.scotland', [
            'years' => $years,
            'councils' => $councils,
            'byCouncil' => $byCouncil,     // use json_encode in Blade for JS charts
            'national' => $national,       // use json_encode in Blade for JS charts
            'biggestDeclines' => $biggestDeclines,
            'biggestIncreases' => $biggestIncreases,
            'baselineYear' => $baselineYear,
            'compareYear' => $compareYear,
            'baselineYearRecent' => $baselineYearRecent,
            'biggestDeclines2020_2025' => $biggestDeclines2020_2025,
            'biggestIncreases2020_2025' => $biggestIncreases2020_2025,
        ]);
    }

    /**
     * England: Region-owned council housing stock (financial years).
     * Prepares:
     * - Per-region series (for a region selector)
     * - Top movers: biggest declines / increases in total stock between first and last years
     */
    public function england(Request $request)
    {
        // Aggregate the LA-level rows into Region-year totals
        $rows = DB::table('england_council_housing_stock')
            ->selectRaw('`year`, `region_name`, SUM(`total_stock`) as total_stock, SUM(`new_builds`) as new_builds, SUM(`acquisitions`) as acquisitions')
            ->groupBy('year', 'region_name')
            // Financial year stored as string e.g. 1978-79; sort by the start year
            ->orderByRaw('CAST(LEFT(`year`, 4) AS UNSIGNED) ASC')
            ->orderBy('region_name')
            ->get();

        // Years (labels) in correct chronological order
        $years = $rows->pluck('year')->unique()->values()->all();

        $baselineYear = $years[0] ?? null;
        $compareYear = $years ? end($years) : null;

        // Regions to include: those with non-zero stock in the last year
        $regions = [];
        if ($compareYear !== null) {
            $regions = $rows
                ->where('year', $compareYear)
                ->where('total_stock', '>', 0)
                ->pluck('region_name')
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        // Restrict rows to active regions only
        $rowsActive = $rows->whereIn('region_name', $regions);

        // Build per-region time series
        // Structure: [RegionName => [Year => [metrics...]]]
        $byRegion = [];
        foreach ($rowsActive as $r) {
            $byRegion[$r->region_name][$r->year] = [
                'total_stock' => (int) $r->total_stock,
                'new_builds' => (int) $r->new_builds,
                'acquisitions' => (int) $r->acquisitions,
            ];
        }

        // England-wide totals by year (sum across regions)
        $national = [];
        foreach ($years as $year) {
            $subset = $rowsActive->where('year', $year);

            $national[$year] = [
                'total_stock' => (int) $subset->sum('total_stock'),
                'new_builds' => (int) $subset->sum('new_builds'),
                'acquisitions' => (int) $subset->sum('acquisitions'),
            ];
        }

        // Movers between first and last years
        $movers = [];
        if ($baselineYear !== null && $compareYear !== null) {
            foreach ($regions as $region) {
                $y0 = $byRegion[$region][$baselineYear]['total_stock'] ?? null;
                $y1 = $byRegion[$region][$compareYear]['total_stock'] ?? null;

                if ($y0 === null || $y1 === null) {
                    continue;
                }

                $delta = $y1 - $y0;
                $pct = ($y0 > 0) ? round(($delta / $y0) * 100, 1) : null;

                $movers[] = [
                    'region' => $region,
                    'year_start' => $baselineYear,
                    'year_end' => $compareYear,
                    'start_stock' => $y0,
                    'end_stock' => $y1,
                    'delta' => $delta,
                    'pct' => $pct,
                ];
            }
        }

        $moversCollection = collect($movers);

        // Top 20 declines
        $biggestDeclines = $moversCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) < 0)
            ->sortBy('delta')
            ->take(20)
            ->values()
            ->all();

        // Top 20 increases
        $biggestIncreases = $moversCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) > 0)
            ->sortByDesc('delta')
            ->take(20)
            ->values()
            ->all();

        // Largest changes for a more recent window (last 5 years)
        // Financial years are stored as strings like "2019-20"; we derive the start year from the first 4 chars.
        $baselineYearRecent = null;
        $compareYearRecent = $compareYear;

        $yearStart = [];
        foreach ($years as $label) {
            $yearStart[$label] = (int) substr($label, 0, 4);
        }

        if ($compareYearRecent !== null && isset($yearStart[$compareYearRecent])) {
            $targetStart = $yearStart[$compareYearRecent] - 5;

            // Prefer an exact match (e.g. 2018-19 when comparing to 2023-24)
            $baselineYearRecent = collect($years)->first(fn ($lbl) => ($yearStart[$lbl] ?? null) === $targetStart);

            // Fallback: choose the first year whose start-year is >= targetStart (keeps the window ~5y)
            if ($baselineYearRecent === null) {
                $baselineYearRecent = collect($years)
                    ->filter(fn ($lbl) => ($yearStart[$lbl] ?? 0) >= $targetStart)
                    ->first();
            }
        }

        $moversRecent = [];
        if ($baselineYearRecent !== null && $compareYearRecent !== null) {
            foreach ($regions as $region) {
                $y0 = $byRegion[$region][$baselineYearRecent]['total_stock'] ?? null;
                $y1 = $byRegion[$region][$compareYearRecent]['total_stock'] ?? null;

                if ($y0 === null || $y1 === null) {
                    continue;
                }

                $delta = $y1 - $y0;
                $pct = ($y0 > 0) ? round(($delta / $y0) * 100, 1) : null;

                $moversRecent[] = [
                    'region' => $region,
                    'year_start' => $baselineYearRecent,
                    'year_end' => $compareYearRecent,
                    'start_stock' => $y0,
                    'end_stock' => $y1,
                    'delta' => $delta,
                    'pct' => $pct,
                ];
            }
        }

        $moversRecentCollection = collect($moversRecent);

        $biggestDeclinesRecent = $moversRecentCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) < 0)
            ->sortBy('delta')
            ->take(20)
            ->values()
            ->all();

        $biggestIncreasesRecent = $moversRecentCollection
            ->filter(fn ($row) => ($row['delta'] ?? 0) > 0)
            ->sortByDesc('delta')
            ->take(20)
            ->values()
            ->all();

        return view('local_authority.england', [
            'years' => $years,
            'regions' => $regions,
            'byRegion' => $byRegion, // use json_encode in Blade for JS charts
            'national' => $national,
            'baselineYearRecent' => $baselineYearRecent,
            'biggestDeclinesRecent' => $biggestDeclinesRecent,
            'biggestIncreasesRecent' => $biggestIncreasesRecent,
            'biggestDeclines' => $biggestDeclines,
            'biggestIncreases' => $biggestIncreases,
            'baselineYear' => $baselineYear,
            'compareYear' => $compareYear,
        ]);
    }
}
