

@extends('layouts.admin')

@section('content')

<div class="max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded shadow mb-6">
        <h1 class="text-2xl font-bold text-zinc-800">Mortgage Arrears Data</h1>
        <p class="text-zinc-600 mt-1">Manage MLAR quarterly arrears values, edit existing entries, or add new data points.</p>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-2 rounded mb-3">
                <ul class="text-sm list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
                {{ session('success') }}
            </div>
        @endif

        {{-- Add new arrears row --}}
        <form action="{{ route('admin.arrears.add') }}" method="POST" class="mb-6">
            @csrf
            <h2 class="text-lg font-semibold mb-3">Add new row</h2>

            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4 items-end">

                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Band</label>
                    <input type="text" name="band" class="border rounded p-2 w-full" required>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm text-zinc-700 mb-1">Description</label>
                    <input type="text" name="description" class="border rounded p-2 w-full" required>
                </div>

                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Year</label>
                    <input type="number" name="year" class="border rounded p-2 w-full" required>
                </div>

                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Quarter</label>
                    <select name="quarter" class="border rounded p-2 w-full" required>
                        <option value="">Select</option>
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Value (%)</label>
                    <input type="number" step="0.001" name="value" class="border rounded p-2 w-full" required>
                </div>

            </div>

            <button class="mt-4 bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                Add Row
            </button>
        </form>

        {{-- Edit existing rows --}}
        <form action="{{ route('admin.arrears.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <button class="bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                    Save Changes
                </button>
            </div>

            <table class="w-full table-auto border-collapse mb-4">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="p-2 border">Band</th>
                        <th class="p-2 border">Description</th>
                        <th class="p-2 border">Year</th>
                        <th class="p-2 border">Quarter</th>
                        <th class="p-2 border">Value (%)</th>
                        <th class="p-2 border">Delete</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($arrears as $i => $row)
                        <tr>
                            <td class="border p-2">
                                <input type="hidden" name="rows[{{$i}}][id]" value="{{ $row->id }}">
                                <input type="text" name="rows[{{$i}}][band]" value="{{ $row->band }}" class="border rounded p-1 w-full">
                            </td>

                            <td class="border p-2">
                                <input type="text" name="rows[{{$i}}][description]" value="{{ $row->description }}" class="border rounded p-1 w-full">
                            </td>

                            <td class="border p-2">
                                <input type="number" name="rows[{{$i}}][year]" value="{{ $row->year }}" class="border rounded p-1 w-full">
                            </td>

                            <td class="border p-2">
                                <select name="rows[{{$i}}][quarter]" class="border rounded p-1 w-full">
                                    <option value="Q1" @selected($row->quarter === 'Q1')>Q1</option>
                                    <option value="Q2" @selected($row->quarter === 'Q2')>Q2</option>
                                    <option value="Q3" @selected($row->quarter === 'Q3')>Q3</option>
                                    <option value="Q4" @selected($row->quarter === 'Q4')>Q4</option>
                                </select>
                            </td>

                            <td class="border p-2">
                                <input type="number" step="0.001" name="rows[{{$i}}][value]" value="{{ $row->value }}" class="border rounded p-1 w-full">
                            </td>

                            <td class="border text-center">
                                <button
                                    type="button"
                                    class="text-red-600 cursor-pointer"
                                    onclick="if (confirm('Are you sure you want to delete this entry?')) { document.getElementById('delete-row-{{ $row->id }}').submit(); }"
                                >
                                    ✖
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button class="bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">Save Changes</button>
        </form>

        @foreach($arrears as $row)
            <form id="delete-row-{{ $row->id }}" action="{{ route('admin.arrears.destroy', $row->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

    </div>
</div>

@endsection