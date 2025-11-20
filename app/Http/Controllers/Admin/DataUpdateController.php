<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataUpdate;
use Illuminate\Http\Request;

class DataUpdateController extends Controller
{
    public function index()
    {
        $updates = DataUpdate::orderBy('next_update_due_at')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.updates.index', compact('updates'));
    }

    public function create()
    {
        $update = new DataUpdate();

        return view('admin.updates.form', [
            'update' => $update,
            'mode'   => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        DataUpdate::create($data);

        return redirect()
            ->route('admin.updates.index')
            ->with('success', 'Update item created.');
    }

    public function edit(DataUpdate $update)
    {
        return view('admin.updates.form', [
            'update' => $update,
            'mode'   => 'edit',
        ]);
    }

    public function update(Request $request, DataUpdate $update)
    {
        $data = $this->validatedData($request);

        $update->update($data);

        return redirect()
            ->route('admin.updates.index')
            ->with('success', 'Update item updated.');
    }

    public function destroy(DataUpdate $update)
    {
        $update->delete();

        return redirect()
            ->route('admin.updates.index')
            ->with('success', 'Update item deleted.');
    }

    protected function validatedData(Request $request): array
    {
        return $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'last_updated_at'   => ['nullable', 'date'],
            'next_update_due_at'=> ['nullable', 'date'],
            'notes'             => ['nullable', 'string'],
            'data_link'         => ['nullable', 'url'],
        ]);
    }
}
