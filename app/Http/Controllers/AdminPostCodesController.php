<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPostCodesController extends Controller
{
    public function index()
    {
        $postcodes = DB::table('prime_postcodes')
            ->select('id', 'postcode', 'category', 'notes')
            ->orderBy('category')
            ->orderBy('postcode')
            ->get();

        return view('admin.postcodes.index', compact('postcodes'));
    }

    public function create()
    {
        return view('admin.postcodes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'postcode' => 'required|string|max:10',
            'category' => 'required|in:Prime Central,Ultra Prime',
            'notes'    => 'nullable|string',
        ]);

        DB::table('prime_postcodes')->insert($validated);

        return redirect()->route('admin.postcodes.index')->with('success', 'Postcode added successfully.');
    }

    public function edit($id)
    {
        $postcode = DB::table('prime_postcodes')->find($id);
        abort_unless($postcode, 404);

        return view('admin.postcodes.edit', compact('postcode'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'postcode' => 'required|string|max:10',
            'category' => 'required|in:Prime Central,Ultra Prime',
            'notes'    => 'nullable|string',
        ]);

        DB::table('prime_postcodes')->where('id', $id)->update($validated);

        return redirect()->route('admin.postcodes.index')->with('success', 'Postcode updated successfully.');
    }

    public function destroy($id)
    {
        DB::table('prime_postcodes')->where('id', $id)->delete();

        return redirect()->route('admin.postcodes.index')->with('success', 'Postcode deleted successfully.');
    }
}
