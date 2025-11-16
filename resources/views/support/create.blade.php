@extends('layouts.app')
@section('content')
<div class="bg-gradient-to-b from-zinc-50 to-white border-b border-zinc-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
        <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-zinc-900">Create a new support ticket</h1>
        <p class="mt-3 text-sm sm:text-base text-zinc-600 max-w-3xl">
            Please provide as much detail as possible so I can respond efficiently.  
            Tickets help me keep everything organised and ensure nothing gets missed.
        </p>
    </div>
</div>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-white rounded-xl shadow-sm border border-zinc-200 p-6 sm:p-8">
        <form method="POST" action="/support" enctype="multipart/form-data" class="space-y-8">
            @csrf
            
            <div>
                <label for="title" class="block text-sm font-semibold text-zinc-800">Ticket title</label>
                <p class="text-xs text-zinc-500 mt-1">A short summary of your issue or question.</p>
                <div class="text-red-500 text-sm mt-1">
                    {{ $errors->has('title') ? 'A title is required' : '' }}
                </div>
                <input 
                    class="mt-2 w-full rounded-md border border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 h-10 px-3"
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                >
            </div>

            <div>
                <label for="text" class="block text-sm font-semibold text-zinc-800">Details</label>
                <p class="text-xs text-zinc-500 mt-1">Describe the issue in as much detail as possible.</p>
                <div class="text-red-500 text-sm mt-1">
                    {{ $errors->has('text') ? 'At least some text is required' : '' }}
                </div>
                <textarea 
                    class="mt-2 w-full rounded-md border border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 p-3 tinymce-support"
                    name="text"
                    id="text"
                    placeholder="Be as detailed as possible"
                >{{ old('text') }}</textarea>
            </div>

            <div>
                <button 
                    type="submit" 
                    class="inline-flex items-center justify-center rounded-md border border-lime-600 bg-lime-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-lime-400"
                >
                    Create ticket
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
    <script>
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: 'textarea.tinymce-support',
                menubar: false,
                height: 320,
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