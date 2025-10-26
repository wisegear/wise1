@extends('layouts.app')

@section('content')

<div class="max-w-5xl mx-auto px-4 py-8 space-y-10">

    {{-- Status Message --}}
    @if (session('status'))
        <div class="rounded-lg border border-lime-300 bg-lime-50 px-4 py-3 text-center text-lime-800 font-medium shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    {{-- Form --}}
    <form action="/profile/{{ $user->name_slug }}" method="post" enctype="multipart/form-data" class="space-y-10">
        @csrf
        @method('PUT')

        {{-- Admin Controls --}}
        @can('Admin')
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-800 mb-4 text-center">Admin Controls</h2>

            <div class="flex flex-wrap justify-center gap-4 mb-4">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                            @foreach ($user->user_roles as $user_role)
                                @if ($user_role->name === $role->name)
                                    checked
                                @endif
                            @endforeach>
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-center gap-6 mb-4 text-sm">
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="trusted" name="trusted" @if ($user->trusted === 1) checked @endif>
                    <span>Trusted Member?</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" id="lock" name="lock" @if ($user->lock === 1) checked @endif>
                    <span>User Locked?</span>
                </label>
            </div>

            <div>
                <label class="font-semibold text-sm mb-1 block" for="notes">Notes</label>
                <textarea name="notes" id="notes" class="w-full rounded-md border border-zinc-300 bg-white p-2 text-sm shadow-sm">{{ $user->notes }}</textarea>
            </div>
        </section>
        @endcan

        {{-- Avatar Section --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-800 mb-4 text-center">Avatar</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-center text-center">
                {{-- Upload --}}
                <div>
                    <p class="font-medium mb-2">Upload New Avatar</p>
                    <label for="image" class="cursor-pointer inline-block rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-black">Choose File</label>
                    <input id="image" type="file" name="image" accept="image/*" onchange="loadFile(event)" class="hidden">
                    <p class="text-xs text-red-600 mt-2">
                        {{ $errors->has('image') ? 'Image must be JPG, JPEG, PNG or GIF. Max size: 500kb.' : '' }}
                    </p>
                </div>

                {{-- Current Avatar --}}
                <div>
                    <p class="font-medium mb-2">Current Avatar</p>
                    <img src="{{ asset('/assets/images/avatars/' . $user->avatar) }}" alt="Current avatar" class="mx-auto shadow-md rounded-full border border-gray-200 w-24 h-24 object-cover">
                </div>

                {{-- Preview New Avatar --}}
                <div>
                    <p class="font-medium mb-2">New Avatar Preview</p>
                    <img id="new_avatar" class="mx-auto shadow-md rounded-full border border-gray-200 w-24 h-24 object-cover">
                </div>
            </div>

            <script>
                var loadFile = function(event) {
                    var new_avatar = document.getElementById('new_avatar');
                    new_avatar.src = URL.createObjectURL(event.target.files[0]);
                };
            </script>
        </section>

        {{-- Profile Details --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm space-y-6">
            <h2 class="text-lg font-semibold text-zinc-800 mb-4 text-center">Profile Details</h2>

            <div>
                <label for="name" class="font-semibold text-sm block mb-1">Username (Locked)</label>
                <input type="text" id="name" name="name" value="{{ $user->name }}" class="w-full rounded-md border border-zinc-300 bg-gray-100 p-2 text-sm shadow-sm" disabled>
                <p class="text-xs text-red-600 mt-1">{{ $errors->has('name') ? ' Cannot be blank, Max 255 characters, must be unique.' : '' }}</p>
            </div>

            <div>
                <label for="email" class="font-semibold text-sm block mb-1">Email</label>
                <input type="text" id="email" name="email" value="{{ $user->email }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
                <p class="text-xs text-red-600 mt-1">{{ $errors->has('email') ? ' Cannot be blank, Max 255 characters, must be unique.' : '' }}</p>
            </div>

            <div class="text-center">
                <input type="checkbox" id="email_visible" name="email_visible" value="{{ $user->email_visible }}" @if ($user->email_visible == true) checked @endif>
                <label for="email_visible" class="text-sm text-zinc-700 ml-2">Display email publicly</label>
            </div>

            <div>
                <label for="website" class="font-semibold text-sm block mb-1">Website <span class="text-zinc-500 font-normal">(example: propertyresearch.uk)</span></label>
                <input type="text" id="website" name="website" value="{{ $user->website }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="location" class="font-semibold text-sm block mb-1">Location</label>
                <input type="text" id="location" name="location" value="{{ $user->location }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="bio" class="font-semibold text-sm block mb-1">About You</label>
                <textarea id="bio" name="bio" rows="6" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">{{ $user->bio }}</textarea>
                <p class="text-xs text-red-600 mt-1">{{ $errors->has('bio') ? ' Max 500 characters' : '' }}</p>
            </div>

            <div class="text-zinc-500 text-sm">
                <p>Only include a username for social media accounts, the site will create the rest of the address, no @ or other symbols.</p>
            </div>        

            <div>
                <label for="linkedin" class="font-semibold text-sm block mb-1">LinkedIn</label>
                <input type="text" id="linkedin" name="linkedin" value="{{ $user->linkedin }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="x" class="font-semibold text-sm block mb-1">Twitter / X</label>
                <input type="text" id="x" name="x" value="{{ $user->x }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="facebook" class="font-semibold text-sm block mb-1">Facebook</label>
                <input type="text" id="facebook" name="facebook" value="{{ $user->facebook }}" class="w-full rounded-md border border-zinc-300 p-2 text-sm shadow-sm">
            </div>

            <div class="text-center pt-4">
                <button type="submit" class="rounded-md bg-zinc-900 px-6 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">Update Profile</button>
            </div>
        </section>
    </form>
</div>

@endsection
