@extends('layouts.app')

@section('content')
    <h2 class="text-2xl text-center font-bold mb-10">Edit Post</h2>

    <div class="w-3/4 mx-auto space-y-8">
        <form method="POST" action="/blog/{{ $page->id }}" enctype="multipart/form-data">
            @csrf
            {{ method_field('PUT') }}

            <!-- Featured Image Section -->
            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-10 mb-10">
                <!-- Upload New Featured Image -->
                <div class="md:w-1/2">
                    <div class="flex flex-col mb-4">
                        <label for="image" class="text-gray-700 mb-2 font-bold">
                            Upload New Featured Image (optional)
                        </label>
                        <input type="file" name="image" id="image" accept="image/*" 
                               onchange="previewImage(event)" class="border rounded p-2">
                    </div>
                    <!-- Featured Image Preview -->
                    <div id="image-preview">
                        <img id="preview" class="w-full" style="display: none;" alt="Featured Image Preview">
                    </div>
                </div>
                <!-- Existing Featured Image -->
                <div class="md:w-1/2">
                    <h2 class="font-bold text-lg mb-4">Existing Featured Image</h2>
                    @if($page->original_image)
                        <img src="{{ '/assets/images/uploads/' . 'small_' . $page->original_image }}"
                             class="w-full h-[350px] object-cover" alt="Featured Image">
                    @else
                        <p class="text-gray-600">No featured image available</p>
                    @endif
                </div>
            </div>

            <!-- Date Field -->
            <div>
                <label class="font-semibold text-gray-700 mb-2">
                    Date of Post <span class="text-gray-400">(dd-mm-yyyy)</span>:
                </label>
                <input class="border rounded text-sm h-8 px-2 w-full mt-2" type="date" name="date"
                       value="{{ old('date', $page->GetRawOriginal('date')) }}">
            </div>

            <!-- Post Title -->
            <div class="mt-3">
                <label class="font-semibold text-gray-700 mb-2">Enter Title:</label>
                <input class="border rounded text-sm h-8 px-2 w-full mt-2" type="text" name="title"
                       value="{{ old('title', $page->title) }}" placeholder="Enter a title for this post">
            </div>

            <!-- Post Summary -->
            <div class="my-10">
                <label class="font-semibold text-gray-700 mb-2">Enter a Summary:</label>
                <textarea class="border rounded text-sm w-full mt-2 p-2" name="summary"
                          placeholder="Enter a summary for this post">{{ old('summary', $page->summary) }}</textarea>
            </div>

            <!-- Post Body with TinyMCE -->
            <div class="my-10">
                <label class="font-semibold text-gray-700 mb-2">Enter the Body of the Post:</label>
                <textarea class="w-full border rounded mt-2 p-2" name="body" id="editor"
                          placeholder="This is the body of the post">{{ old('body', $page->body) }}</textarea>
            </div>

            <!-- TinyMCE Script -->
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

            <!-- Upload Additional Images for Editor -->
            <div class="my-10">
                <label class="font-semibold text-gray-700 mb-2">Upload Additional Images for Editor:</label>
                <input type="file" name="images[]" id="editorImages" multiple class="border rounded p-2">
            </div>

            <!-- Display Uploaded Images for Editor -->
            <div class="my-10" id="uploaded-images-preview">
                <h4 class="font-bold mb-2">Uploaded Images for Use in the Editor</h4>
                @if($page->images)
                    <div class="grid grid-cols-4 gap-4">
                        @foreach(json_decode($page->images) as $image)
                            <div class="border space-y-4 p-2">
                                <img src="{{ asset($image) }}" class="h-[100px] w-full object-cover">
                                <button type="button" onclick="copyToClipboard('{{ asset($image) }}')"
                                        class="py-1 px-2 border rounded">
                                    <i class="fa-regular fa-clipboard"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p>No additional images have been uploaded yet.</p>
                @endif
            </div>

            <!-- Gallery Images Section -->
            <div class="flex flex-col md:flex-row gap-8 my-10">
                <!-- Existing Gallery Images -->
                <div class="md:w-1/2">
                    <h3 class="text-xl font-bold mb-4">Uploaded Gallery Images</h3>
                    @if($page->gallery_images)
                        <div id="existing-gallery-images" class="grid grid-cols-2 gap-4">
                            @foreach(json_decode($page->gallery_images, true) as $galleryImage)
                                <div class="border p-2 rounded">
                                    <img src="{{ asset('/assets/images/uploads/galleries/' . $galleryImage['original']) }}"
                                         class="w-full h-auto mb-2" alt="Gallery Image">
                                    <button type="button"
                                            onclick="copyToClipboard('{{ asset('/assets/images/uploads/galleries/' . $galleryImage['original']) }}')"
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
                <!-- Upload New Gallery Images -->
                <div class="md:w-1/2">
                    <h3 class="text-xl font-bold mb-4">Upload Gallery Images</h3>
                    <label for="gallery_images" class="block mb-2">Select Multiple Images:</label>
                    <input type="file" name="gallery_images[]" id="gallery_images" multiple
                           class="border rounded p-2"
                           onchange="previewMultipleImages(event, 'gallery-preview')">
                    <!-- Preview container for new gallery images -->
                    <div id="gallery-preview" class="mt-2 flex flex-wrap"></div>
                </div>
            </div>

            <!-- Category Selection -->
            <div class="border rounded border-gray-300 p-4 my-10">
                <h3 class="font-semibold text-gray-700 mb-2">Select a Category for the Post:</h3>
                <div class="flex flex-wrap gap-4">
                    @foreach ($categories as $category)
                        <div class="flex items-center">
                            <input type="radio" id="category_{{ $category->id }}" name="category"
                                   value="{{ $category->id }}" class="mr-2"
                                   @if ($page->categories_id === $category->id) checked @endif>
                            <label for="category_{{ $category->id }}">{{ $category->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Post Tags -->
            <div class="my-10">
                <label class="font-semibold text-gray-700 mb-2">Enter Tags:</label>
                <input type="text" class="w-full border rounded text-sm  h-8 px-2" name="tags"
                       value="{{ $split_tags }}" placeholder="Enter tags for the post, e.g., one-two-three">
            </div>

            <!-- Post Options -->
            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Post Options:</h3>
                <div class="flex gap-6 border rounded border-gray-300 py-2 px-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="published" name="published" class="mr-2"
                               @if ($page->published) checked @endif>
                        <label for="published">Publish?</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="featured" name="featured" class="mr-2"
                               @if ($page->featured) checked @endif>
                        <label for="featured">Featured?</label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit"
                        class="my-10 border p-2 bg-lime-600 rounded text-white text-sm hover:bg-green-500">
                    Update Post
                </button>
            </div>
        </form>
    </div>

    <!-- JavaScript Functions -->
    <script>
        // Preview for Featured Image
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                const preview = document.getElementById('preview');
                preview.src = reader.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // Preview for Multiple Gallery Images
        function previewMultipleImages(event, previewContainerId) {
            const container = document.getElementById(previewContainerId);
            container.innerHTML = ''; // Clear previous previews
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Gallery Image Preview';
                    img.classList.add('w-24', 'h-24', 'object-cover', 'mr-2', 'mb-2', 'rounded');
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }

        // Function to copy image URL to clipboard
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