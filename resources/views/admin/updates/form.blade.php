@extends('layouts.admin')

@section('title', $mode === 'create' ? 'Add Data Update' : 'Edit Data Update')

@section('content')
<div class="max-w-5xl mx-auto px-4">
    {{-- Header / Hero --}}
    <section class="rounded-xl border border-zinc-200 bg-white p-8 shadow-sm mb-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                    Admin Console
                </div>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">
                    {{ $mode === 'create' ? 'Add Data Update' : 'Edit Data Update' }}
                </h1>
                <p class="mt-3 text-sm md:text-base leading-7 text-zinc-500 max-w-2xl">
                    Track when each dataset was last updated and when the next update is due.
                </p>
            </div>
        </div>
    </section>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 shadow-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Card --}}
    <section class="rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">
        <form
            action="{{ $mode === 'create' ? route('admin.updates.store') : route('admin.updates.update', $update) }}"
            method="POST"
            class="space-y-8"
        >
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $update->name) }}"
                        class="mt-2 block w-full rounded-md border-zinc-300 text-sm shadow-sm focus:border-zinc-900 focus:ring-zinc-900"
                        required
                    >
                    <p class="mt-1 text-xs text-zinc-500">
                        e.g. “MLAR Arrears (Quarterly)”, “Land Registry PPD (Monthly)”.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700">
                        Link to Data
                    </label>
                    <input
                        type="url"
                        name="data_link"
                        value="{{ old('data_link', $update->data_link) }}"
                        class="mt-2 block w-full rounded-md border-zinc-300 text-sm shadow-sm focus:border-zinc-900 focus:ring-zinc-900"
                        placeholder="https://..."
                    >
                    <p class="mt-1 text-xs text-zinc-500">
                        Direct link to the source (ONS, BoE, FCA, etc.) or your internal page.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-zinc-700">
                        Date Last Updated
                    </label>
                    <input
                        type="date"
                        name="last_updated_at"
                        value="{{ old('last_updated_at', optional($update->last_updated_at)->format('Y-m-d')) }}"
                        class="mt-2 block w-full rounded-md border-zinc-300 text-sm shadow-sm focus:border-zinc-900 focus:ring-zinc-900"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700">
                        Date Next Update Expected
                    </label>
                    <input
                        type="date"
                        name="next_update_due_at"
                        value="{{ old('next_update_due_at', optional($update->next_update_due_at)->format('Y-m-d')) }}"
                        class="mt-2 block w-full rounded-md border-zinc-300 text-sm shadow-sm focus:border-zinc-900 focus:ring-zinc-900"
                    >
                    <p class="mt-1 text-xs text-zinc-500">
                        Expected release date — adjust later if they slip.
                    </p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-zinc-700">
                    Notes
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    class="mt-2 block w-full rounded-md border-zinc-300 text-sm shadow-sm focus:border-zinc-900 focus:ring-zinc-900"
                >{{ old('notes', $update->notes) }}</textarea>
                <p class="mt-1 text-xs text-zinc-500">
                    Add details like release patterns, revision expectations, or internal tasks.
                </p>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4">
                <a
                    href="{{ route('admin.updates.index') }}"
                    class="text-sm text-zinc-600 hover:underline"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center rounded-md bg-zinc-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-black"
                >
                    {{ $mode === 'create' ? 'Create' : 'Save changes' }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection