<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MortgageApproval;

class AdminMortgageApprovalController extends Controller
{
    /**
     * Display table of approvals (most recent first).
     */
    public function index()
    {
        $approvals = MortgageApproval::orderBy('period', 'desc')->get();

        return view('admin.mortgageapprovals.index', compact('approvals'));
    }

    /**
     * Add a new approval entry.
     */
    public function add(Request $request)
    {
        $data = $request->validate([
            'series_code' => 'required|string|max:32',
            'period'      => 'required|date',
            'value'       => 'nullable|integer',
            'unit'        => 'nullable|string|max:16',
            'source'      => 'nullable|string|max:64',
        ]);

        MortgageApproval::create($data);

        return back()->with('success', 'Mortgage approval entry added successfully.');
    }

    /**
     * Update existing approval rows.
     */
    public function store(Request $request)
    {
        $rows = $request->input('rows', []);

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;

            if (!$id) {
                continue;
            }

            $approval = MortgageApproval::find($id);

            if (!$approval) {
                continue;
            }

            // Only update fields that are present and non-empty
            if (array_key_exists('series_code', $row) && $row['series_code'] !== '') {
                $approval->series_code = $row['series_code'];
            }

            if (array_key_exists('period', $row) && $row['period'] !== '') {
                $approval->period = $row['period'];
            }

            if (array_key_exists('value', $row)) {
                $approval->value = $row['value'] !== '' ? $row['value'] : null;
            }

            if (array_key_exists('unit', $row)) {
                $approval->unit = $row['unit'] !== '' ? $row['unit'] : null;
            }

            if (array_key_exists('source', $row) && $row['source'] !== '') {
                $approval->source = $row['source'];
            }

            $approval->save();
        }

        return back()->with('success', 'Mortgage approvals updated successfully.');
    }

    /**
     * Delete specific approval row.
     */
    public function destroy($id)
    {
        MortgageApproval::findOrFail($id)->delete();

        return back()->with('success', 'Mortgage approval entry deleted.');
    }
}
