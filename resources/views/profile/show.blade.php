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
                        Email Not shared
                    @else
                        {{ $user->email }}
                    @endif
                </div>
            </div>
        </div>

        @if (Auth::user()->name_slug === $user->name_slug || Auth::user()->has_user_role('Admin'))
            <form action="/profile/{{ $user->name_slug }}/edit" method="GET">
                <button type="submit" class="inline-flex items-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-zinc-600 cursor-pointer">Edit Profile</button>
            </form>
        @endif
    </div>

    {{-- Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Main column --}}
        <div class="md:col-span-2 space-y-4">
            {{-- About / Bio --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="flex gap-2 text-base font-semibold text-zinc-900 mb-2 pb-2 border-b border-b-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    About
                </h2>
                @if (empty($user->bio))
                    <p class="text-zinc-500">User has not provided any information about themselves.</p>
                @else
                    <p class="whitespace-pre-line leading-relaxed text-zinc-800 text-sm">{{ $user->bio }}</p>
                @endif
            </section>

            {{-- Recent Posts --}}
            @if($userPosts->count())
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="flex gap-2 text-base font-semibold text-zinc-900 mb-2 pb-2 border-b border-b-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    Recent posts by this user
               </h2>
                <ul class="space-y-6 text-sm">
                    @foreach ($userPosts as $post)
                        <li class="flex flex-col border-l-2 pl-3 text-lime-700">
                            <a href="/blog/{{ $post->slug }}" class="text-zinc-800 underline decoration-zinc-400 hover:decoration-lime-600 hover:text-lime-600 font-medium">
                                {{ $post->title }}
                            </a>
                            <span class="text-xs text-zinc-500">{{ $post->created_at->format('d M Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif

            {{-- Recent Comments --}}
            @if($userComments->count())
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="flex gap-2 text-base font-semibold text-zinc-900 mb-4 pb-2 border-b border-b-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                    </svg>
                    Recent comments made by this user
                </h2>
                <ul class="space-y-6 text-sm">
                    @foreach ($userComments as $c)
                        <li class="border-l-2 pl-3 text-lime-700">
                            @if($c['post_slug'])
                                <a href="/blog/{{ $c['post_slug'] }}" class="text-zinc-800 underline decoration-zinc-400 hover:decoration-lime-600 font-medium">
                                    {{ $c['post_title'] }}
                                </a>
                            @else
                                <span class="text-zinc-800 font-medium">[Post no longer available]</span>
                            @endif
                            <div class="text-xs text-zinc-500">
                                {{ \Carbon\Carbon::parse($c['comment_created_at'])->format('d M Y') }}
                            </div>
                            <p class="mt-2 text-zinc-700 leading-relaxed">{{ $c['comment_body'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            {{-- Details --}}
            <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="flex gap-2 text-base font-semibold text-zinc-900 mb-2 pb-2 border-b border-b-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21m0 0h4.5V3.545M12.75 21h7.5V10.75M2.25 21h1.5m18 0h-18M2.25 9l4.5-1.636M18.75 3l-1.5.545m0 6.205 3 1m1.5.5-1.5-.5M6.75 7.364V3h-3v18m3-13.636 10.5-3.819" />
                    </svg>
                    Details
                </h2>
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
                <h2 class="flex gap-2 text-base font-semibold text-zinc-900 mb-2 pb-2 border-b border-b-zinc-200">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-lime-700">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                    </svg>
                    Social
                </h2>
                <ul class="space-y-2 text-sm">
                    @if($user->x)
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-zinc-600">X</span>
                            <a href="https://x.com/{{ $user->x }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">Visit</a>
                        </li>
                    @endif
                    @if($user->facebook)
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-zinc-600">Facebook</span>
                            <a href="https://www.facebook.com/{{ $user->facebook }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">Visit</a>
                        </li>
                    @endif
                    @if($user->linkedin)
                        <li class="flex items-center justify-between gap-3">
                            <span class="text-zinc-600">LinkedIn</span>
                            <a href="https://www.linkedin.com/in/{{ $user->linkedin }}" class="underline decoration-zinc-400 hover:decoration-lime-600" target="_blank" rel="noopener">Visit</a>
                        </li>
                    @endif
                </ul>
            </section>

        </aside>
    </div>
</div>

@endsection