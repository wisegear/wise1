@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto">

    <div class="">
        @if (session('status'))
            <div class="text-center text-green-800 text-2xl font-bold my-5 border py-1 px-2 bg-gray-100">
                {{ session('status') }}
            </div>
        @endif
    </div>
    
        <form action="/profile/{{ $user->name_slug }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Allows an Admin to alter user groups, set trusted, add notes, remove user lock -->
        @can('Admin')
            <div class="border border-slate-300 p-4 rounded-lg shadow-lg bg-slate-100 text-sm">
                
                <div class="text-center">
                    <p class="font-bold">Admin Controls</p>
                </div>

                <div class="my-4 flex justify-center space-x-4">
                    @foreach ($roles as $role)
                        <div class="">
                            <input class="" type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                
                                @foreach ($user->user_roles as $user_role)
                                    @if ($user_role->name === $role->name)
                                        checked
                                    @endif
                                @endforeach
                                >
                            <label class="" for="roles">{{ $role->name }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center">       
                        <ul class="flex space-x-4">           
                            <li class="">
                                <label>Trusted Member?</label>     
                                <input type="checkbox" class="" id="trusted" name="trusted" @if ($user->trusted === 1) checked=checked @endif>
                            </li>
                            <li class="">
                                <label>User Locked?</label>     
                                <input type="checkbox" class="" id="lock" name="lock" @if ($user->lock === 1) checked=checked @endif>
                            </li>
                        </ul>
                </div>
                        
                <div class="flex flex-col">
                    <label class="font-bold mb-2">Notes</label>
                    <textarea class="text-sm bg-white p-2 rounded border border-slate-300" name="notes" id="notes">{{ $user->notes }}</textarea>
                </div>          
            
            </div>
        @endcan

        <div class="my-10 flex flex-col items-center space-y-10 md:flex-row md:justify-evenly md:space-y-0 md:space-x-6">
            <!-- Upload New Avatar -->
            <div class="flex flex-col items-center w-full max-w-xs">
                <p class="font-semibold mb-2 text-center">Upload a New Avatar</p>

                <label for="image" class="bg-slate-500 hover:bg-slate-400 text-white text-sm font-medium py-2 px-4 rounded cursor-pointer">
                    Choose File
                </label>
                <input id="image" type="file" name="image" accept="image/*" onchange="loadFile(event)" class="hidden">

                <p class="text-sm text-red-600 mt-2">
                    {{ $errors->has('image') ? 'Image must be JPG, JPEG, PNG or GIF. Max size: 500kb.' : '' }}
                </p>
            </div>

            <!-- Existing Avatar -->
            <div class="flex flex-col items-center w-full max-w-xs">
                <p class="font-semibold mb-2 text-center">Current Avatar</p>
                <img src="{{ asset("/assets/images/avatars/$user->avatar") }}" class="mx-auto shadow-md rounded-full border border-gray-200" style="width: 100px; height: 100px;">
            </div>

            <!-- Preview New Avatar -->
            <div class="flex flex-col items-center w-full max-w-xs">
                <p class="font-semibold mb-2 text-center">New Avatar Preview</p>
                <img id="new_avatar" class="shadow-md rounded-full border border-gray-200" style="width: 100px; height: 100px;">
            </div>
        </div>

        <script>
            var loadFile = function(event) {
                var new_avatar = document.getElementById('new_avatar');
                new_avatar.src = URL.createObjectURL(event.target.files[0]);
            };
        </script>
    
        <div class="my-10 w-full md:w-1/2 md:mx-auto">
            <div class="flex flex-col space-y-6">
                  <div class="">
                    <label for="name" class="font-semibold">Username (Locked, open a ticket to change username)</label>
                    <input type="text" class="border rounded-md w-full bg-gray-100 p-2" id="name" name="name" value="{{ $user->name }}" disabled>
                    <p class="text-red-500">{{ $errors->has('name') ? ' Cannot be blank, Max 255 characters, must be unique.' : '' }}</p>
                  </div>
    
                  <div class="">
                    <label for="email" class="font-semibold">Email</label>
                    <input type="text" class="border rounded-md w-full p-2" id="email" name="email" value="{{ $user->email }}">
                    <p class="text-red-500">{{ $errors->has('email') ? ' Cannot be blank, Max 255 characters, must be unique.' : '' }}</p>
                  </div>
    
                  <div class="text-center pt-4">
                    <input type="checkbox" class="font-semibold" id="email_visible" name="email_visible" value="{{ $user->email_visible }}" @if ($user->email_visible == true) checked @endif>
                    <label class="" for="email_visible">Checking this box will display your email publically.</label>
                  </div>
    
                  <div class="">
                    <label for="website" class="font-semibold">Website</label>
                    <input type="text" class="border rounded-md w-full p-2" id="website" name="website" value="{{ $user->website }}">
                  </div>
    
                  <div class="">
                    <label for="location" class="font-semibold">Location</label>
                    <input type="text" class="border rounded-md w-full p-2" id="location" name="location" value="{{ $user->location }}">
                  </div>
    
                  <div class="">
                    <label for="bio" class="font-semibold">Tell everyone a bit about you</label>
                    <textarea class="border rounded-md w-full p-2" id="bio" name="bio" rows="6">{{ $user->bio }}</textarea>
                    <p class="text-red-500">{{ $errors->has('bio') ? ' Max 500 characters' : '' }}</p>
                  </div>
    
                  <div class="">
                    <label for="linkedin" class="font-semibold">Linkedin</label>
                    <input type="text" class="border rounded-md w-full p-2" id="linkedin" name="linkedin" value="{{ $user->linkedin }}">
                  </div>
    
                  <div class="">
                    <label for="twitter" class="font-semibold">Twitter</label>
                    <input type="text" class="border rounded-md w-full p-2" id="x" name="x" value="{{ $user->x }}">
                  </div>
    
                  <div class="">
                    <label for="facebook" class="font-semibold">Facebook</label>
                    <input type="text" class="border rounded-md w-full p-2" id="facebook" name="facebook" value="{{ $user->facebook }}">
                  </div>
    
                  <button type="submit" class="standard-button md:w-1/3 md:mx-auto">Update Profile</button>

            </div>
        </form>
        </div>

    </div>
    
    </div>

@endsection
