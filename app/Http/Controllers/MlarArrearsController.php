<?php

namespace App\Http\Controllers;

use App\Models\MlarArrear;

class MlarArrearsController extends Controller
{
    public function index()
    {
        // Distinct bands with their descriptions (for labels / legend)
        $bands = MlarArrear::select('band', 'description')
            ->distinct()
            ->orderBy('band')
            ->get()
            ->keyBy('band');

        // Full time series grouped by band (for charts / tables)
        $seriesByBand = MlarArrear::orderBy('year')
            ->orderByRaw("FIELD(quarter, 'Q1','Q2','Q3','Q4')")
            ->get()
            ->groupBy('band');

        // Ordered list of periods like "2007 Q1", "2007 Q2", ...
        $periods = MlarArrear::select('year', 'quarter')
            ->distinct()
            ->orderBy('year')
            ->orderByRaw("FIELD(quarter, 'Q1','Q2','Q3','Q4')")
            ->get()
            ->map(fn ($row) => $row->year . ' ' . $row->quarter)
            ->values();

        // Latest quarter (for headline numbers)
        $latest = MlarArrear::select('year', 'quarter')
            ->orderBy('year', 'desc')
            ->orderByRaw("FIELD(quarter, 'Q4','Q3','Q2','Q1')")
            ->first();

        $latestValues = null;

        if ($latest) {
            $latestValues = MlarArrear::where('year', $latest->year)
                ->where('quarter', $latest->quarter)
                ->orderBy('band')
                ->get();
        }

        return view('arrears.index', [
            'bands'         => $bands,
            'seriesByBand'  => $seriesByBand,
            'periods'       => $periods,
            'latest'        => $latest,
            'latestValues'  => $latestValues,
        ]);
    }
}
