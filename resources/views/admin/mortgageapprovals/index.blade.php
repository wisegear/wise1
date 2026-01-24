

@extends('layouts.admin')

@section('content')

<div class="max-w-7xl mx-auto">

    {{-- Hero --}}
    <div class="relative z-0 overflow-hidden bg-white p-6 rounded shadow mb-6">
        @include('partials.hero-background')
        <h1 class="text-2xl font-bold text-zinc-800">Mortgage Approvals</h1>
        <p class="text-zinc-600 mt-1">Manage monthly mortgage approval values from the Bank of England.</p>
    </div>

    <div class="bg-white p-6 rounded shadow mb-6">

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-2 rounded mb-3">
                <ul class="text-sm list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success --}}
        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
                {{ session('success') }}
            </div>
        @endif

        {{-- Add New Entry --}}
        <form action="{{ route('admin.approvals.add') }}" method="POST" class="mb-6">
            @csrf
            <div class="grid grid-cols-5 gap-4">
                <input type="text" name="series_code" placeholder="Series Code (e.g. LPMVTVX)"
                       class="border rounded p-2 w-full" required>

                <input type="date" name="period"
                       class="border rounded p-2 w-full" required>

                <input type="number" name="value" placeholder="Value"
                       class="border rounded p-2 w-full" step="1">

                <input type="text" name="unit" placeholder="Unit"
                       class="border rounded p-2 w-full">

                <input type="text" name="source" placeholder="Source"
                       class="border rounded p-2 w-full" value="BoE">
            </div>

            <button class="mt-3 bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                Add Entry
            </button>
        </form>

        {{-- Edit Existing Rows --}}
        <form action="{{ route('admin.approvals.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <button class="bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                    Save Changes
                </button>
            </div>

            <table class="w-full table-auto border-collapse mb-4">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="p-2 border">Series Code</th>
                        <th class="p-2 border">Period</th>
                        <th class="p-2 border">Value</th>
                        <th class="p-2 border">Unit</th>
                        <th class="p-2 border">Source</th>
                        <th class="p-2 border">Delete</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($approvals as $i => $row)
                        <tr>
                            <td class="border p-2">
                                <input type="hidden" name="rows[{{$i}}][id]" value="{{ $row->id }}">
                                <input type="text" name="rows[{{$i}}][series_code]" value="{{ $row->series_code }}"
                                       class="border rounded p-1 w-full" required>
                            </td>

                            <td class="border p-2">
                                <input type="date" name="rows[{{$i}}][period]" value="{{ $row->period->format('Y-m-d') }}"
                                       class="border rounded p-1 w-full" required>
                            </td>

                            <td class="border p-2">
                                <input type="number" step="1" name="rows[{{$i}}][value]" value="{{ $row->value }}"
                                       class="border rounded p-1 w-full">
                            </td>

                            <td class="border p-2">
                                <input type="text" name="rows[{{$i}}][unit]" value="{{ $row->unit }}"
                                       class="border rounded p-1 w-full">
                            </td>

                            <td class="border p-2">
                                <input type="text" name="rows[{{$i}}][source]" value="{{ $row->source }}"
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

            <button class="bg-zinc-800 hover:bg-zinc-900 text-white px-4 py-2 rounded">
                Save Changes
            </button>

        </form>

        {{-- Delete Forms --}}
        @foreach($approvals as $row)
            <form id="delete-row-{{ $row->id }}" action="{{ route('admin.approvals.destroy', $row->id) }}" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

    </div>
</div>

@endsection
