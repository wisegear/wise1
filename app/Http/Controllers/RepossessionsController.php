<?php
// app/Http/Controllers/RepossessionsController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RepoLaQuarterly as Repo;
use Illuminate\Support\Arr;

class RepossessionsController extends Controller
{
    /**
     * /repossessions index
     *
     * Shows grouped repossession counts with filters and a period toggle.
     * - period: 'quarterly' (default) or 'yearly'
     * - by:      'type' (default) or 'action'
     * - filters: year, quarter (for quarterly), year_from/year_to (for yearly),
     *            county (county_ua), region, type, action
     */
    public function index(Request $request)
    {
        /* -------------------- 1) Validate & read query params -------------------- */
        $validated = $request->validate([
            'period'     => 'nullable|in:quarterly,yearly',
            'by'         => 'nullable|in:type,action',
            'year'       => 'nullable|integer',
            'quarter'    => 'nullable|in:Q1,Q2,Q3,Q4',
            'year_from'  => 'nullable|integer',
            'year_to'    => 'nullable|integer',
            'county'     => 'nullable|string',
            'region'     => 'nullable|string',
            'type'       => 'nullable|string',
            'action'     => 'nullable|string',
            'per_page'   => 'nullable|integer|min:10|max:500',
            'stage'      => 'nullable|string',
        ]);

        $period = $validated['period'] ?? 'quarterly';           // 'quarterly' | 'yearly'
        $by     = $validated['by']     ?? 'type';                // 'type' | 'action'
        $byCol  = $by === 'action' ? 'possession_action' : 'possession_type';
        $stage  = isset($validated['stage']) ? trim($validated['stage']) : null;
        $stageNorm = $stage ? strtolower(preg_replace('/\s+/', ' ', $stage)) : null;
        $stageLike = $stageNorm ? "%{$stageNorm}%" : null;

        $actionNorm = isset($validated['action']) && $validated['action'] !== ''
            ? strtolower(preg_replace('/\s+/', ' ', trim($validated['action'])))
            : null;
        $typeNorm = isset($validated['type']) && $validated['type'] !== ''
            ? strtolower(preg_replace('/\s+/', ' ', trim($validated['type'])))
            : null;

        // Data for dropdowns (distinct lists)
        $years    = Repo::query()->select('year')->distinct()->orderBy('year')->pluck('year');
        $regions  = Repo::query()->selectRaw("DISTINCT TRIM(region) AS region")->orderBy('region')->pluck('region');
        $counties = Repo::query()
            ->selectRaw("DISTINCT TRIM(REPLACE(county_ua,' UA','')) AS county")
            ->orderBy('county')
            ->pluck('county');
        $types    = Repo::query()->select('possession_type')->distinct()->orderBy('possession_type')->pluck('possession_type');
        $actions  = Repo::query()->select('possession_action')->distinct()->orderBy('possession_action')->pluck('possession_action');

        // Defaults for period selection
        [$latestYear, $latestQuarter] = Repo::latestPeriod(); // e.g. [2025, 'Q2']
        $perPage = $validated['per_page'] ?? 100;

        /* -------------------- 2) Build the grouped query -------------------- */
        if ($period === 'yearly') {
            // Year range defaults: latest year if none given
            $maxYear = (int) ($years->max() ?? $latestYear);
            $minYear = (int) ($years->min() ?? $latestYear);
            $yearFrom = (int) ($validated['year_from'] ?? $maxYear);
            $yearTo   = (int) ($validated['year_to']   ?? $yearFrom);

            // Ensure from <= to and within data range
            if ($yearFrom > $yearTo) {
                [$yearFrom, $yearTo] = [$yearTo, $yearFrom];
            }
            $yearFrom = max($minYear, $yearFrom);
            $yearTo   = min($maxYear, $yearTo);

            $query = Repo::query()
                ->selectRaw("year, county_ua, {$byCol} AS reason, SUM(value) AS cases")
                ->whereBetween('year', [$yearFrom, $yearTo])
                ->when($validated['county'] ?? null, fn($q,$v)=>$q->whereRaw("TRIM(REPLACE(county_ua,' UA','')) = ?", [$v]))
                ->when($validated['region'] ?? null, fn($q,$v)=>$q->where('region',$v))
                ->when(($by === 'type') && $typeNorm,   function ($q) use ($typeNorm) {
                    $q->whereRaw('LOWER(TRIM(possession_type)) = ?', [$typeNorm]);
                })
                ->when(($by === 'action') && $actionNorm, function ($q) use ($actionNorm) {
                    $q->whereRaw('LOWER(TRIM(possession_action)) = ?', [$actionNorm]);
                })
                ->when($stageLike, function ($q) use ($stageLike) {
                    $q->where(function ($qq) use ($stageLike) {
                        $qq->whereRaw('LOWER(TRIM(possession_action)) LIKE ?', [$stageLike])
                           ->orWhereRaw('LOWER(TRIM(possession_type)) LIKE ?', [$stageLike]);
                    });
                })
                ->groupBy('year','county_ua',$byCol)
                ->orderBy('year')->orderBy('county_ua')->orderBy('reason');
            
            $meta = [
                'period'     => 'yearly',
                'by'         => $by,
                'year_from'  => $yearFrom,
                'year_to'    => $yearTo,
                'quarters'   => ['Q1','Q2','Q3','Q4'], // for UI consistency
            ];
        } else {
            // Quarterly (default): use latest if not provided
            $year    = (int) ($validated['year'] ?? $latestYear);
            $quarter = (string) ($validated['quarter'] ?? $latestQuarter);

            $query = Repo::query()
                ->selectRaw("county_ua, {$byCol} AS reason, SUM(value) AS cases")
                ->where('year', $year)
                ->where('quarter', $quarter)
                ->when($validated['county'] ?? null, fn($q,$v)=>$q->whereRaw("TRIM(REPLACE(county_ua,' UA','')) = ?", [$v]))
                ->when($validated['region'] ?? null, fn($q,$v)=>$q->where('region',$v))
                ->when(($by === 'type') && $typeNorm,   function ($q) use ($typeNorm) {
                    $q->whereRaw('LOWER(TRIM(possession_type)) = ?', [$typeNorm]);
                })
                ->when(($by === 'action') && $actionNorm, function ($q) use ($actionNorm) {
                    $q->whereRaw('LOWER(TRIM(possession_action)) = ?', [$actionNorm]);
                })
                ->when($stageLike, function ($q) use ($stageLike) {
                    $q->where(function ($qq) use ($stageLike) {
                        $qq->whereRaw('LOWER(TRIM(possession_action)) LIKE ?', [$stageLike])
                           ->orWhereRaw('LOWER(TRIM(possession_type)) LIKE ?', [$stageLike]);
                    });
                })
                ->groupBy('county_ua',$byCol)
                ->orderBy('county_ua')->orderBy('reason');

            $meta = [
                'period'   => 'quarterly',
                'by'       => $by,
                'year'     => $year,
                'quarter'  => $quarter,
                'quarters' => ['Q1','Q2','Q3','Q4'],
            ];
        }

        /* -------------------- 3) Execute (with pagination) -------------------- */
        $rows = $query->paginate($perPage)->withQueryString();

        /* -------------------- 4) Totals (for header chips/cards) -------------- */
        // Build an ungrouped base query using the same filters (no SELECT/GROUP BY here)
        $base = Repo::query();

        if ($period === 'yearly') {
            $base->whereBetween('year', [$yearFrom, $yearTo])
                 ->when($validated['county'] ?? null, fn($q,$v)=>$q->whereRaw("TRIM(REPLACE(county_ua,' UA','')) = ?", [$v]))
                 ->when($validated['region'] ?? null, fn($q,$v)=>$q->where('region',$v))
                 ->when(($by === 'type') && $typeNorm,   function ($q) use ($typeNorm) {
                     $q->whereRaw('LOWER(TRIM(possession_type)) = ?', [$typeNorm]);
                 })
                 ->when(($by === 'action') && $actionNorm, function ($q) use ($actionNorm) {
                     $q->whereRaw('LOWER(TRIM(possession_action)) = ?', [$actionNorm]);
                 })
                 ->when($stageLike, function ($q) use ($stageLike) {
                     $q->where(function ($qq) use ($stageLike) {
                         $qq->whereRaw('LOWER(TRIM(possession_action)) LIKE ?', [$stageLike])
                            ->orWhereRaw('LOWER(TRIM(possession_type)) LIKE ?', [$stageLike]);
                     });
                 });
        } else {
            $base->where('year', $year)
                 ->where('quarter', $quarter)
                 ->when($validated['county'] ?? null, fn($q,$v)=>$q->whereRaw("TRIM(REPLACE(county_ua,' UA','')) = ?", [$v]))
                 ->when($validated['region'] ?? null, fn($q,$v)=>$q->where('region',$v))
                 ->when(($by === 'type') && $typeNorm,   function ($q) use ($typeNorm) {
                     $q->whereRaw('LOWER(TRIM(possession_type)) = ?', [$typeNorm]);
                 })
                 ->when(($by === 'action') && $actionNorm, function ($q) use ($actionNorm) {
                     $q->whereRaw('LOWER(TRIM(possession_action)) = ?', [$actionNorm]);
                 })
                 ->when($stageLike, function ($q) use ($stageLike) {
                     $q->where(function ($qq) use ($stageLike) {
                         $qq->whereRaw('LOWER(TRIM(possession_action)) LIKE ?', [$stageLike])
                            ->orWhereRaw('LOWER(TRIM(possession_type)) LIKE ?', [$stageLike]);
                     });
                 });
        }

        // Total cases for the current filters (sum of raw values)
        $totalCases = (clone $base)->sum('value');

        // Totals by reason (type/action) for chips and chart
        $byReason = (clone $base)
            ->selectRaw("{$byCol} AS reason, SUM(value) AS total_cases")
            ->groupBy('reason')
            ->orderBy('reason')
            ->get();

        /* -------------------- 5) Send to the view ----------------------------- */
        return view('repossessions.index', [
            'rows'      => $rows,       // paginated grouped results
            'meta'      => $meta,       // period/by + selected filters
            'years'     => $years,      // for selects
            'regions'   => $regions,
            'counties'  => $counties,
            'types'     => $types,
            'actions'   => $actions,
            'totals'    => [
                'all'     => (int) ($totalCases ?? 0),
                'byReason'=> $byReason,
            ],
        ]);
    }
    /**
     * Economic Indicators: Repossessions Overview
     */
    public function overview()
    {
        // Load full series grouped by year (sum of cases per year)
        $yearly = Repo::query()
            ->selectRaw('year, SUM(value) AS total')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        // Latest year
        $latest = $yearly->last();
        $previous = $yearly->count() > 1 ? $yearly[$yearly->count() - 2] : null;

        $yoy = null;
        if ($latest && $previous) {
            $yoy = (int)$latest->total - (int)$previous->total;
        }

        // Prepare chart arrays
        $labels = $yearly->pluck('year')->values()->toJson();
        $values = $yearly->pluck('total')->values()->toJson();

        return view('repossessions.overview', [
            'yearly'  => $yearly,
            'latest'  => $latest,
            'previous'=> $previous,
            'yoy'     => $yoy,
            'labels'  => $labels,
            'values'  => $values,
        ]);
    }
}
