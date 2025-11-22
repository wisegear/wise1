<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MlarArrear;

class AdminArrearsController extends Controller
{
    /**
     * Show all arrears rows.
     */
    public function index()
    {
        $arrears = MlarArrear::orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->get();

        return view('admin.arrears.index', compact('arrears'));
    }

    /**
     * Add a brand-new arrears row.
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'band' => 'required|string',
            'description' => 'required|string',
            'year' => 'required|integer',
            'quarter' => 'required|string',
            'value' => 'required|numeric',
        ]);

        MlarArrear::create($data);

        return back()->with('success', 'New arrears row added.');
    }

    /**
     * Save edits to existing rows.
     */
    public function store(Request $request)
    {
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;

            // Only update existing rows
            if (empty($id)) {
                continue;
            }

            $arrear = MlarArrear::find($id);

            if (!$arrear) {
                continue;
            }

            // Update only fields that are actually present; don't overwrite with nulls
            if (array_key_exists('band', $row) && $row['band'] !== '') {
                $arrear->band = $row['band'];
            }

            if (array_key_exists('description', $row) && $row['description'] !== '') {
                $arrear->description = $row['description'];
            }

            if (array_key_exists('year', $row) && $row['year'] !== '') {
                $arrear->year = $row['year'];
            }

            // Quarter and value must not become null; only update if posted and non-empty
            if (array_key_exists('quarter', $row) && $row['quarter'] !== '') {
                $arrear->quarter = $row['quarter'];
            }

            if (array_key_exists('value', $row) && $row['value'] !== '') {
                $arrear->value = $row['value'];
            }

            $arrear->save();
        }

        return back()->with('success', 'Arrears data updated successfully.');
    }

    /**
     * Delete a row.
     */
    public function destroy($id)
    {
        MlarArrear::findOrFail($id)->delete();

        return back()->with('success', 'Row deleted.');
    }
}
