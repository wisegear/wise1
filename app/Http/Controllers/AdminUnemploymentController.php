<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnemploymentMonthly;

class AdminUnemploymentController extends Controller
{
    public function index()
    {
        $unemployment = UnemploymentMonthly::orderBy('date', 'desc')->get();

        return view('admin.unemployment.index', compact('unemployment'));
    }

    /**
     * Handle adding a brand new unemployment row (from the top form).
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'rate' => 'required|numeric',
        ]);

        UnemploymentMonthly::create($data);

        return back()->with('success', 'New unemployment row added.');
    }

    /**
     * Handle saving edits to existing rows (from the table form).
     */
    public function store(Request $request)
    {
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $id   = $row['id']   ?? null;
            $date = $row['date'] ?? null;
            $rate = $row['rate'] ?? null;

            // We only expect existing rows here; if there is no ID, skip.
            if (empty($id)) {
                continue;
            }

            // Optionally skip completely empty edits (both fields blank)
            if (($date === null || $date === '') && ($rate === null || $rate === '')) {
                continue;
            }

            UnemploymentMonthly::where('id', $id)->update([
                'date' => $date,
                'rate' => $rate,
            ]);
        }

        return back()->with('success', 'Unemployment data saved successfully.');
    }

    public function destroy($id)
    {
        UnemploymentMonthly::findOrFail($id)->delete();

        return back()->with('success', 'Row deleted.');
    }
}