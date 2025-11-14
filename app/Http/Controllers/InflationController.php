<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InflationCpihMonthly;

class InflationController extends Controller
{
    public function index()
    {
        // Fetch all monthly rows, oldest → newest
        $all = InflationCpihMonthly::orderBy('date')->get();

        if ($all->isEmpty()) {
            return view('inflation.index', [
                'latest' => null,
                'previous' => null,
                'labels' => [],
                'values' => [],
                'yearly' => collect(),
            ]);
        }

        // Latest record
        $latest = $all->last();

        // Previous month (if exists)
        $previous = $all->count() > 1 ? $all[$all->count() - 2] : null;

        // For chart (monthly CPIH %)
        $labels = $all->map(fn($r) => $r->date->format('Y-m-d'));
        $values = $all->map(fn($r) => (float) $r->rate);

        // Yearly aggregation (annual averages)
        $yearly = $all
            ->groupBy(fn($r) => $r->date->format('Y'))
            ->map(function ($rows) {
                return round($rows->avg('rate'), 2);
            })
            ->map(function ($avg, $year) {
                return (object)[
                    'year' => (int) $year,
                    'avg_rate' => $avg
                ];
            })
            ->values()
            ->sortBy('year');

        return view('inflation.index', [
            'all' => $all,
            'latest' => $latest,
            'previous' => $previous,
            'labels' => $labels,
            'values' => $values,
            'yearly' => $yearly,
        ]);
    }
}
