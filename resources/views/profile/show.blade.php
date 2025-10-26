@extends('layouts.app')

@section('content')

<div class="mx-auto max-w-7xl px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="h-20 w-20 rounded-full overflow-hidden border border-zinc-200 bg-zinc-100">
                <img class="h-full w-full object-cover" src="{{ asset("/assets/images/avatars/$user->avatar") }}" alt="{{ $user->name }} avatar">
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ $user->name }}</h1>
                <div class="mt-1 text-sm text-zinc-600">
                    {{-- Email visibility follows existing logic --}}
                    @if($user->email_visible === 0)
                        Not shared
                    @else
                        {{ $user->email }}
                    @endif
                </div>
            </div>
        </div>

        @if (Auth::user()->name_slug === $user->name_slug || Auth::user()->has_user_role('Admin'))
            <form action="/profile/{{ $user->name_slug }}/edit" method="GET">
                <button type="submit" class="inline-flex items-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">Edit Profile</button>
            </form>
        @endif
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Main column --}}
        <div class="md:col-span-2 space-y-4">
            {{-- About / Bio --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-900 mb-2">About</h2>
                @if (empty($user->bio))
                    <p class="text-zinc-500">User has not provided any information about themselves.</p>
                @else
                    <p class="whitespace-pre-line leading-relaxed text-zinc-800 text-sm">{{ $user->bio }}</p>
                @endif
            </section>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            {{-- Details --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-900 mb-2">Details</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-600">Website</dt>
                        <dd class="text-zinc-900 truncate">
                            @if($user->website)
                                <a class="underline decoration-zinc-400 hover:decoration-lime-600" href="https://{{ $user->website }}">{{ $user->website }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-600">Location</dt>
                        <dd class="text-zinc-900">{{ $user->location ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-zinc-600">Email</dt>
                        <dd class="text-zinc-900">
                            @if($user->email_visible === 0)
                                Not shared
                            @else
                                {{ $user->email }}
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Social --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-zinc-900 mb-2">Social</h2>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-zinc-600">X</span>
                        <span>
                            @if($user->x)
                                <a href="https://x.com/{{ $user->x }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">{{ $user->x }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-zinc-600">Facebook</span>
                        <span>
                            @if($user->facebook)
                                <a href="https://www.facebook.com/{{ $user->facebook }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">{{ $user->facebook }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-zinc-600">LinkedIn</span>
                        <span>
                            @if($user->linkedin)
                                <a href="https://www.linkedin.com/in/{{ $user->linkedin }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">{{ $user->linkedin }}</a>
                            @else
                                —
                            @endif
                        </span>
                    </li>
                </ul>
            </section>
        </aside>
    </div>
</div>

@endsection