@extends('layouts.app')
@section('content')
<div class="bg-gradient-to-b from-zinc-50 to-white border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900">Support tickets</h1>
                <p class="mt-3 text-sm sm:text-base text-zinc-600">
                    If you have any questions about the service or queries in general, please open a ticket rather than sending an email.
                    This allows me to manage all user requests more quickly and in a more organised way.
                </p>
                <p class="mt-2 text-xs sm:text-sm text-zinc-500">
                    Please note that no email notifications are sent when tickets are responded to.
                    A notification will be displayed in the top right of the site instead.
                </p>
            </div>
            <div class="flex lg:flex-col gap-3">
                <a href="/support/create" class="inline-flex items-center justify-center rounded-md border border-lime-600 bg-lime-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-lime-400">
                    Create new ticket
                </a>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="bg-white rounded-xl shadow-sm border border-zinc-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500 border-b border-zinc-200">
                    <tr>
                        <th class="px-4 py-3 text-left w-2/5">Ticket</th>
                        <th class="px-4 py-3 text-left w-1/5">Opened</th>
                        <th class="px-4 py-3 text-left w-1/5">Last response</th>
                        <th class="px-4 py-3 text-left w-1/5">Last replied by</th>
                        <th class="px-4 py-3 text-left w-40 whitespace-nowrap">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-4 py-3 align-top">
                                <a href="/support/{{ $ticket->id }}" class="text-sm font-medium text-lime-600 hover:text-lime-800 hover:underline">
                                    {{ $ticket->title }}
                                </a>
                            </td>
                            <td class="px-4 py-3 align-top text-zinc-700">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                            @if ($ticket->comments->last() != null)
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

                                <span class="inline-flex items-center rounded-lg px-3 py-1 text-xs font-semibold whitespace-nowrap {{ $classes }}">
                                    {{ $status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-zinc-500">
                                You don't have any support tickets yet. Use the button above to create your first ticket.
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