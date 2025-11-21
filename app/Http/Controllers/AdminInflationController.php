<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InflationCPIHMonthly;

class AdminInflationController extends Controller
{
    public function index()
    {
        $inflation = InflationCPIHMonthly::orderBy('date', 'desc')->get();
        return view('admin.inflation.index', compact('inflation'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rows.*.date' => 'required|date',
            'rows.*.rate' => 'required|numeric',
        ]);

        foreach ($validated['rows'] as $row) {
            InflationCPIHMonthly::updateOrCreate(
                ['date' => $row['date']],
                ['rate' => $row['rate']]
            );
        }

        return back()->with('success', 'Inflation data saved successfully.');
    }

    public function destroy($id)
    {
        InflationCPIHMonthly::findOrFail($id)->delete();
        return back()->with('success', 'Row deleted.');
    }
}
