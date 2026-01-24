@extends('layouts.admin')
@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Hero / header card --}}
        <div class="relative z-0 overflow-hidden bg-white border border-zinc-200 rounded-xl shadow-sm px-5 py-6 sm:px-8 sm:py-7 mb-8">
            @include('partials.hero-background')
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-100">Admin Console</span>
                    <h1 class="mt-3 text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900">Support tickets</h1>
                    <p class="mt-2 text-sm text-zinc-600 max-w-2xl">
                        Review and manage user support tickets. Use this view to track open issues, recent replies,
                        and ticket status across the site.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3 justify-start sm:justify-end">
                    <a href="/admin/support" class="inline-flex items-center rounded-md border border-zinc-300 bg-white px-3.5 py-1.5 text-xs sm:text-sm font-medium text-zinc-800 shadow-sm hover:bg-zinc-50">
                        Show open tickets
                    </a>
                    <a href="/admin/support?closed=true" class="inline-flex items-center rounded-md border border-zinc-300 bg-zinc-900 px-3.5 py-1.5 text-xs sm:text-sm font-medium text-white shadow-sm hover:bg-zinc-800">
                        Show closed tickets
                    </a>
                </div>
            </div>
        </div>

        {{-- Tickets table card --}}
        <div class="bg-white border border-zinc-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-zinc-800">All tickets</h2>
                <p class="text-xs text-zinc-500">{{ $tickets->total() }} total</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 border-b border-zinc-200">
                        <tr>
                            <th class="px-4 py-3 text-left w-2/5">Title</th>
                            <th class="px-4 py-3 text-left w-1/6">Opened by</th>
                            <th class="px-4 py-3 text-left w-1/6">Last response</th>
                            <th class="px-4 py-3 text-left w-1/6">Last replied by</th>
                            <th class="px-4 py-3 text-center w-16">Replies</th>
                            <th class="px-4 py-3 text-left w-40">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($tickets as $ticket)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-4 py-3 align-top">
                                    <a href="/support/{{ $ticket->id }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ $ticket->title }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 align-top text-zinc-700">
                                    {{ $ticket->users->name }}
                                </td>
                                @if ($ticket->comments && $ticket->comments->isNotEmpty())
                                    <td class="px-4 py-3 align-top text-zinc-700">
                                        {{ $ticket->comments->last()->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 align-top text-zinc-700">
                                        {{ $ticket->comments->last()->user->name }}
                                    </td>
                                @else
                                    <td class="px-4 py-3 align-top text-zinc-400">---</td>
                                    <td class="px-4 py-3 align-top text-zinc-400">---</td>
                                @endif
                                <td class="px-4 py-3 align-top text-center text-zinc-700">
                                    {{ $ticket->comments->count() }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    @php
                                        $status = $ticket->status;
                                        $classes = match($status) {
                                            'Open' => 'bg-lime-500 text-white',
                                            'In Progress' => 'bg-orange-500 text-white',
                                            'Awaiting Reply' => 'bg-yellow-500 text-white',
                                            'Closed' => 'bg-red-500 text-white',
                                            default => 'bg-zinc-200 text-zinc-700',
                                        };
                                    @endphp

                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap {{ $classes }}">
                                        {{ $status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-zinc-500">
                                    There are no tickets to display.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="px-4 py-3 border-t border-zinc-100 bg-zinc-50">
                    <div class="flex justify-center">
                        {{ $tickets->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
