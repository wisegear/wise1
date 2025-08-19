@extends('layouts.app')

@section('content')
    <h2 class="text-2xl text-center font-bold mb-10">Create a New Post</h2>

    <div class="w-3/4 mx-auto space-y-8">
        <form method="POST" action="/blog" enctype="multipart/form-data">
            @csrf

            <!-- Featured Image Upload -->
            <div class="flex flex-col">
                <label for="image" class="mb-2 font-semibold">Upload Featured Image</label>
                <input type="file" name="image" id="image" accept="image/*" required
                       onchange="previewImage(event)"
                       class="border rounded p-2">
            </div>

            <!-- Featured Image Preview -->
            <div id="image-preview" class="my-6">
                <img id="preview" class="w-full hidden" alt="Featured Image Preview">
            </div>

            <!-- Post Date -->
            <div>
                <label for="date" class="font-semibold text-gray-700">
                    Enter Date of Post <span class="text-gray-400">(dd-mm-yyyy)</span>:
                </label>
                <input type="date" id="date" name="date" value="{{ old('date') }}"
                       class="border rounded text-sm h-8 px-2 w-full mt-2">
                @if($errors->has('date'))
                    <div class="mt-2 text-red-500">{{ $errors->first('date') }}</div>
                @endif
            </div>

            <!-- Post Title -->
            <div>
                <label for="title" class="font-semibold text-gray-700">Enter Title:</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                       placeholder="Enter a title for this post"
                       class="border rounded text-sm h-8 px-2 w-full mt-2">
                @if($errors->has('title'))
                    <div class="mt-2 text-red-500">{{ $errors->first('title') }}</div>
                @endif
            </div>

            <!-- Post Summary -->
            <div>
                <label for="excerpt" class="font-semibold text-gray-700">Enter a Summary:</label>
                <textarea id="excerpt" name="summary" placeholder="Enter a summary for this post"
                          class="border rounded text-sm w-full mt-2 p-2">{{ old('summary') }}</textarea>
                @if($errors->has('summary'))
                    <div class="mt-2 text-red-500">{{ $errors->first('summary') }}</div>
                @endif
            </div>

            <!-- Post Body -->
            <div>
                <label for="editor" class="font-semibold text-gray-700">Enter the Body of the Post:</label>
                <textarea name="body" id="editor" placeholder="This is the body of the post"
                          class="w-full border rounded mt-2 p-2">{{ old('body') }}</textarea>
                @if($errors->has('body'))
                    <div class="mt-2 text-red-500">{{ $errors->first('body') }}</div>
                @endif
            </div>

            <!-- New Image Upload Field (Images to be displayed within the post) -->
            <div>
                <label for="images" class="font-semibold text-gray-700">Upload Images for This Post</label>
                <input type="file" name="images[]" id="images" multiple
                       class="border rounded p-2 mt-2"
                       onchange="previewMultipleImages(event, 'inpost-preview')">
                <!-- Container to preview selected in-post images -->
                <div id="inpost-preview" class="mt-2 flex flex-wrap"></div>
            </div>

            <!-- Gallery Images Section -->
            <div class="flex flex-col md:flex-row gap-8 mt-6">
                <!-- Display Uploaded Gallery Images -->
                <div class="md:w-1/2">
                    <h3 class="text-xl font-bold mb-4">Uploaded Gallery Images</h3>
                    @if(isset($post) && $post->gallery_images)
                        <div id="uploaded-gallery-images" class="grid grid-cols-2 gap-4">
                            @foreach(json_decode($post->gallery_images) as $image)
                                <div class="border p-2 rounded">
                                    <img src="{{ asset($image) }}" class="w-full h-auto mb-2" alt="Gallery Image">
                                    <button type="button"
                                            onclick="copyToClipboard('{{ asset($image) }}')"
                                            class="bg-blue-500 text-white text-sm px-2 py-1 rounded">
                                        Copy URL
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-600">No gallery images uploaded yet.</p>
                    @endif
                </div>
                <!-- Upload Gallery Images -->
                <div class="md:w-1/2">
                    <h3 class="text-xl font-bold mb-4">Upload Gallery Images</h3>
                    <label for="gallery_images" class="block mb-2">Select Multiple Images:</label>
                    <input type="file" name="gallery_images[]" id="gallery_images" multiple
                           class="border rounded p-2"
                           onchange="previewMultipleImages(event, 'gallery-preview')">
                    <!-- Container to preview selected gallery images -->
                    <div id="gallery-preview" class="mt-2 flex flex-wrap"></div>
                </div>
            </div>

            <!-- Category Selection -->
            <div class="border rounded border-gray-300 p-4">
                <h3 class="font-semibold text-gray-700 mb-2">Select a Category for the Post:</h3>
                <div class="flex flex-wrap gap-4">
                    @foreach ($categories as $category)
                        <div class="flex items-center">
                            <input type="radio" id="category_{{ $category->id }}" name="category"
                                   value="{{ $category->id }}" class="mr-2">
                            <label for="category_{{ $category->id }}">{{ $category->name }}</label>
                        </div>
                    @endforeach
                </div>
                @if($errors->has('category'))
                    <div class="mt-2 text-red-500">{{ $errors->first('category') }}</div>
                @endif
            </div>

            <!-- Post Tags -->
            <div>
                <label for="tags" class="font-semibold text-gray-700">Enter Tags (if any):</label>
                <input type="text" id="tags" name="tags"
                       placeholder="Enter tags for the post, e.g., one-two-three"
                       class="w-full border rounded text-sm h-8 px-2 mt-2">
            </div>

            <!-- Post Options -->
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Post Options:</h3>
                <div class="flex gap-6 border rounded border-gray-300 py-2 px-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="published" name="published" class="mr-2">
                        <label for="published">Publish?</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="featured" name="featured" class="mr-2">
                        <label for="featured">Featured?</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit"
                        class="mt-6 border p-2 bg-lime-600 rounded text-white text-sm hover:bg-green-500">
                    Create New Post
                </button>
            </div>
        </form>
    </div>

    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/a1rn9rzvnlulpzdgoe14w7kqi1qpfsx7cx9am2kbgg226dqz/tinymce/7/tinymce.min.js"
            referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#editor',
            plugins: 'advlist autolink lists link image charmap preview anchor code fullscreen insertdatetime media table paste help wordcount',
            toolbar: 'undo redo | h1 h2 h3 | formatselect | bold italic backcolor | table | alignleft aligncenter alignright alignjustify | bullist numlist | removeformat | image | help',
            menubar: 'file edit view insert format tools table help',
            branding: false,
            block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6;'
        });
    </script>

    <!-- JavaScript for Image Previews and Copy URL -->
    <script>
        // Preview for Featured Image
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const preview = document.getElementById('preview');
                preview.src = reader.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // Preview for multiple image selections (in-post and gallery)
        function previewMultipleImages(event, previewContainerId) {
            const container = document.getElementById(previewContainerId);
            container.innerHTML = ''; // Clear previous previews
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Image Preview';
                    // Adjust the styling as needed
                    img.classList.add('w-24', 'h-24', 'object-cover', 'mr-2', 'mb-2', 'rounded');
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }

        // Function to copy the image URL to the clipboard
        function copyToClipboard(text) {
            const tempInput = document.createElement('input');
            document.body.appendChild(tempInput);
            tempInput.value = text;
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('Image URL copied to clipboard!');
        }
    </script>
@endsection