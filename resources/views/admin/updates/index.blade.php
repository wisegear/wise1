@extends('layouts.admin')

@section('title', 'Data Updates')

@section('content')
<div class="max-w-7xl mx-auto px-4">

    {{-- Header --}}
    <section class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white p-8 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white/70 px-3 py-1 text-xs text-zinc-700 shadow-sm">
                <span class="h-2 w-2 rounded-full bg-lime-500"></span>
                Admin Console
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 md:text-4xl">Data Updates</h1>
            <p class="mt-3 text-sm md:text-base leading-7 text-zinc-500 max-w-2xl">
                Track when data sources were last updated and when the next updates are due.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.updates.create') }}"
               class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black">
                + Add Update
            </a>
        </div>
    </section>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Table --}}
    <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
        @if($updates->isEmpty())
            <p class="text-sm text-zinc-600">No update items yet. Add your first one.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 text-left text-zinc-600">
                            <th class="px-4 py-2 font-semibold">Name</th>
                            <th class="px-4 py-2 font-semibold">Last Updated</th>
                            <th class="px-4 py-2 font-semibold">Next Update</th>
                            <th class="px-4 py-2 font-semibold">Link</th>
                            <th class="px-4 py-2 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200">
                        @foreach($updates as $update)
                            <tr class="@if(optional($update->next_update_due_at)->isPast()) bg-red-50 @endif">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-zinc-900">{{ $update->name }}</div>
                                    @if($update->notes)
                                        <p class="mt-1 text-xs text-zinc-500">{{ Str::limit($update->notes, 100) }}</p>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-zinc-700">
                                    {{ $update->last_updated_at?->format('d M Y') ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-zinc-700">
                                    {{ $update->next_update_due_at?->format('d M Y') ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    @if($update->data_link)
                                        <a href="{{ $update->data_link }}" target="_blank"
                                           class="text-cyan-700 hover:underline break-all">Open</a>
                                    @else
                                        <span class="text-xs text-zinc-400">None</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('admin.updates.edit', $update) }}"
                                           class="text-sm text-blue-600 hover:underline">Edit</a>

                                        <form action="{{ route('admin.updates.destroy', $update) }}"
                                              method="POST"
                                              onsubmit="return confirm('Delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-sm text-red-600 hover:underline">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $updates->links() }}
            </div>
        @endif
    </section>
</div>
@endsection