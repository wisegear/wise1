@extends('layouts.app')
@section('content')
<div class="bg-gradient-to-b from-zinc-50 to-white border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900">{{ $ticket->title }}</h1>
        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-zinc-600">
            <span>Opened by <span class="font-medium text-zinc-800">{{ $ticket->users->name }}</span></span>
            <span>•</span>
            <span>{{ $ticket->created_at->diffForHumans() }}</span>
            <span>•</span>
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
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-white rounded-xl shadow-sm border border-zinc-200 p-6 sm:p-8">
        <div class="wise1text max-w-none">
            {!! $ticket->text !!}
        </div>

        <form method="POST" action="/support/{{ $ticket->id }}" class="mt-8">
            @csrf
            @method('PUT')

            <div class="flex flex-wrap gap-3">
                @if ($ticket->status === 'Open' || $ticket->status === 'In Progress' || $ticket->status === 'Awaiting Reply')
                    <button type="submit" name="closeTicket" value="true"
                        class="inline-flex items-center rounded-md bg-red-500 hover:bg-red-400 px-2 py-2 text-xs font-semibold text-white shadow-sm">
                        Close Ticket
                    </button>
                @elseif ($ticket->status === 'Closed')
                    <button type="submit" name="openTicket" value="true"
                        class="inline-flex items-center rounded-md bg-lime-500 hover:bg-lime-400 px-4 py-2 text-sm font-semibold text-white shadow-sm">
                        Reopen Ticket
                    </button>
                @endif

                @can('Admin')
                    <button type="submit" name="inProgress" value="true"
                        class="inline-flex items-center rounded-md bg-orange-500 hover:bg-orange-400 px-2 py-2 text-xs  font-semibold text-white shadow-sm">
                        In Progress
                    </button>

                    <button type="submit" name="AwaitingReply" value="true"
                        class="inline-flex items-center rounded-md bg-yellow-500 hover:bg-yellow-400 px-2 py-2 text-xs font-semibold text-white shadow-sm">
                        Awaiting Reply
                    </button>
                @endcan
            </div>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 mb-6">Ticket Replies</h2>

    @forelse ($ticket->comments as $comment)
        <div class="rounded-xl p-5 sm:p-6 mb-6 bg-white border border-zinc-300 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 mb-3">
                <span class="font-medium text-zinc-800">{{ $comment->user->name }}</span>
                <span>•</span>
                <span>{{ $comment->created_at->diffForHumans() }}</span>
            </div>

            <div class="wise1text max-w-none">
                {!! $comment->comment_text !!}
            </div>
        </div>
    @empty
        <p class="text-zinc-500">No replies yet.</p>
    @endforelse
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <div class="bg-white rounded-xl shadow-sm border border-zinc-200 p-6 sm:p-8">
        <form method="POST" action="/support/{{ $ticket->id }}">
            @csrf
            @method('PUT')

            <label for="text" class="block text-sm font-semibold text-zinc-800">Add a Reply</label>
            <div class="text-red-500 text-sm mt-1">
                {{ $errors->has('comment') ? 'You need to tell us something before replying :)' : '' }}
            </div>

            <textarea 
                name="comment"
                class="mt-2 w-full rounded-md border border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 p-3 tinymce-support-reply"
                placeholder="Write your reply here..."
            ></textarea>

            <button 
                type="submit"
                class="mt-4 inline-flex items-center rounded-md border border-lime-600 bg-lime-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-lime-400"
            >
                Add Reply
            </button>
        </form>
    </div>
</div>
@push('scripts')
    <script>
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: 'textarea.tinymce-support-reply',
                menubar: false,
                height: 260,
                plugins: 'link lists autolink',
                toolbar: 'undo redo | bold italic underline | bullist numlist | link removeformat',
                branding: false,
                default_link_target: '_blank',
                link_assume_external_targets: true,
            });
        }
    </script>
@endpush
@endsection