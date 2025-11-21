<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WageGrowthMonthly;

class AdminWageGrowthController extends Controller
{
    /**
     * Show all wage growth rows.
     */
    public function index()
    {
        $wagegrowth = WageGrowthMonthly::orderBy('date', 'desc')->get();

        return view('admin.wagegrowth.index', compact('wagegrowth'));
    }

    /**
     * Add a brand‑new wage growth row (from the top form).
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'single_month_yoy' => 'nullable|numeric',
            'three_month_avg_yoy' => 'nullable|numeric',
        ]);

        WageGrowthMonthly::create($data);

        return back()->with('success', 'New wage growth row added.');
    }

    /**
     * Save edits to existing rows (from the table form).
     */
    public function store(Request $request)
    {
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $id   = $row['id']   ?? null;
            $date = $row['date'] ?? null;
            $smy  = $row['single_month_yoy'] ?? null;
            $tmy  = $row['three_month_avg_yoy'] ?? null;

            // Only update rows that have an ID
            if (empty($id)) {
                continue;
            }

            // Skip if everything blank (optional safety)
            if (
                ($date === null || $date === '') &&
                ($smy === null || $smy === '') &&
                ($tmy === null || $tmy === '')
            ) {
                continue;
            }

            WageGrowthMonthly::where('id', $id)->update([
                'date' => $date,
                'single_month_yoy' => $smy,
                'three_month_avg_yoy' => $tmy,
            ]);
        }

        return back()->with('success', 'Wage growth data saved successfully.');
    }

    /**
     * Delete a single row.
     */
    public function destroy($id)
    {
        WageGrowthMonthly::findOrFail($id)->delete();

        return back()->with('success', 'Row deleted.');
    }
}
