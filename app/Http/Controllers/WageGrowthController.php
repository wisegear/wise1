<?php

namespace App\Http\Controllers;

use App\Models\WageGrowthMonthly;

class WageGrowthController extends Controller
{
    public function index()
    {
        // Load all rows oldest → newest
        $all = WageGrowthMonthly::orderBy('date')->get();

        if ($all->isEmpty()) {
            return view('wage_growth.index', [
                'all' => collect(),
                'latest' => null,
                'previous' => null,
                'labels_single' => [],
                'values_single' => [],
                'labels_three' => [],
                'values_three' => [],
            ]);
        }

        // Latest and previous month
        $latest = $all->last();
        $previous = $all->count() > 1 ? $all[$all->count() - 2] : null;

        // Chart arrays
        $labels = $all->map(fn($r) => $r->date->format('Y-m-d'))->values();

        $values_single = $all->map(function($r){
            return $r->single_month_yoy !== null ? (float)$r->single_month_yoy : null;
        })->values();

        $values_three = $all->map(function($r){
            return $r->three_month_avg_yoy !== null ? (float)$r->three_month_avg_yoy : null;
        })->values();

        return view('wage_growth.index', [
            'all' => $all,
            'latest' => $latest,
            'previous' => $previous,
            'labels' => $labels,
            'values_single' => $values_single,
            'values_three' => $values_three,
        ]);
    }
}
