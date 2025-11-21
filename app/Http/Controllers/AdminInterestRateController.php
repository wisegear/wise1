<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterestRate;

class AdminInterestRateController extends Controller
{
    /**
     * Show all interest rate rows.
     */
    public function index()
    {
        $interestrates = InterestRate::orderBy('effective_date', 'desc')->get();

        return view('admin.interestrates.index', compact('interestrates'));
    }

    /**
     * Add a brand‑new interest rate row (from the top form).
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'effective_date' => 'required|date',
            'rate' => 'required|numeric',
            'source' => 'nullable|string|max:64',
            'notes' => 'nullable|string',
        ]);

        InterestRate::create($data);

        return back()->with('success', 'New interest rate row added.');
    }

    /**
     * Save edits to existing rows (from the table form).
     */
    public function store(Request $request)
    {
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $id    = $row['id'] ?? null;
            $date  = $row['effective_date'] ?? null;
            $rate  = $row['rate'] ?? null;
            $src   = $row['source'] ?? null;
            $notes = $row['notes'] ?? null;

            // Only update existing rows (no creation here)
            if (empty($id)) {
                continue;
            }

            // Skip completely blank rows for safety
            if (
                ($date === null || $date === '') &&
                ($rate === null || $rate === '') &&
                ($src === null || $src === '') &&
                ($notes === null || $notes === '')
            ) {
                continue;
            }

            InterestRate::where('id', $id)->update([
                'effective_date' => $date,
                'rate' => $rate,
                'source' => $src,
                'notes' => $notes,
            ]);
        }

        return back()->with('success', 'Interest rate data saved successfully.');
    }

    /**
     * Delete a single row.
     */
    public function destroy($id)
    {
        InterestRate::findOrFail($id)->delete();

        return back()->with('success', 'Row deleted.');
    }
}
