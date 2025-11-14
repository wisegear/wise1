<?php

namespace App\Http\Controllers;

use App\Models\UnemploymentMonthly;
use Illuminate\Contracts\View\View;

class UnemploymentController extends Controller
{
    /**
     * Display the unemployment dashboard.
     */
    public function index(): View
    {
        // Fetch all monthly records ordered by date
        $series = UnemploymentMonthly::orderBy('date')->get();

        if ($series->isEmpty()) {
            return view('unemployment.index', [
                'series'          => collect(),
                'latest'          => null,
                'previousYear'    => null,
                'yearOnYearDelta' => null,
                'labels'          => json_encode([]),
                'values'          => json_encode([]),
            ]);
        }

        $latest = $series->last();

        // Same month, previous year (for YoY comparison)
        $previousYear = UnemploymentMonthly::whereDate(
            'date',
            $latest->date->copy()->subYear()
        )->first();

        $yearOnYearDelta = $previousYear
            ? round($latest->rate - $previousYear->rate, 2)
            : null;

        // Use full series from 1971 onward for the chart
        $chartSeries = $series;

        $labels = $chartSeries
            ->map(fn ($row) => $row->date->format('M Y'))
            ->values();

        $values = $chartSeries
            ->map(fn ($row) => round($row->rate, 2))
            ->values();

        return view('unemployment.index', [
            'series'          => $series,
            'latest'          => $latest,
            'previousYear'    => $previousYear,
            'yearOnYearDelta' => $yearOnYearDelta,
            'labels'          => $labels->toJson(),
            'values'          => $values->toJson(),
        ]);
    }
}
