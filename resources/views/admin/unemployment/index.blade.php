@extends('layouts.admin')

@section('content')

<div class="max-w-7xl mx-auto">
    <div class="bg-white p-6 rounded shadow mb-6">
        <h1 class="text-2xl font-bold text-zinc-800">Unemployment Data</h1>
        <p class="text-zinc-600 mt-1">Manage monthly unemployment values, edit existing entries, or add new data points.</p>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">

        {{-- Add new unemployment row --}}
        <form action="{{ route('admin.unemployment.add') }}" method="POST" class="mb-6">
            @csrf
            <h2 class="text-lg font-semibold mb-3">Add new row</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Date (YYYY-MM-01)</label>
                    <input type="date" name="date" class="border rounded p-2 w-full" required>
                </div>
                <div>
                    <label class="block text-sm text-zinc-700 mb-1">Rate (%)</label>
                    <input type="number" step="0.01" name="rate" class="border rounded p-2 w-full" required>
                </div>
                <div>
                    <button class="mt-1 bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded w-full sm:w-auto">
                        Add Row
                    </button>
                </div>
            </div>
        </form>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
                {{ session('success') }}
            </div>
        @endif

        <form action="" method="POST">
            @csrf

            <div class="mb-4">
                <button class="bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                    Save Changes
                </button>
            </div>

            <table class="w-full table-auto border-collapse mb-4">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="p-2 border">Date (YYYY-MM-01)</th>
                        <th class="p-2 border">Rate (%)</th>
                        <th class="p-2 border">Delete</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($unemployment as $i => $row)
                        <tr>
                            <input type="hidden" name="rows[{{$i}}][id]" value="{{ $row->id }}">
                            <td class="border p-2">
                                <input type="date" name="rows[{{$i}}][date]" value="{{ $row->date->format('Y-m-d') }}"
                                       class="border rounded p-1 w-full">
                            </td>
                            <td class="border p-2">
                                <input type="number" step="0.01" name="rows[{{$i}}][rate]" value="{{ $row->rate }}"
                                       class="border rounded p-1 w-full">
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

        @foreach($unemployment as $row)
            <form id="delete-row-{{ $row->id }}" action="/admin/unemployment/{{ $row->id }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

    </div>
</div>

@endsection