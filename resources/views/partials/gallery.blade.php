{{-- Gallery Section Header --}}
<h2 class="wise1text">Gallery</h2>
<p class="text-center">Click on images to see bigger versions.</p>

{{-- Gallery Grid: Responsive layout - 2 cols mobile, 3 cols tablet, 4 cols desktop --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-4 mb-10">
    @foreach($galleryImages as $image)
        <div class="shadow-lg">
            {{-- VenoBox lightbox link: Opens full-size image in overlay --}}
            <a href="{{ asset('assets/images/uploads/galleries/' . $image['original']) }}" 
               class="venobox" 
               data-gall="gallery">
                {{-- Thumbnail image with responsive sizing --}}
                <img src="{{ asset('assets/images/uploads/galleries/' . $image['thumbnail']) }}" 
                     alt="Gallery Image" 
                     class="w-full h-auto rounded shadow-lg border border-rounded border-gray-300">
            </a>
        </div>
    @endforeach
</div>

{{-- VenoBox Lightbox Initialization --}}
<script>
    new VenoBox({
        selector: 'a.venobox',      // Target all links with 'venobox' class
        border: '5px',               // Border width around lightbox
        numeration: true,            // Show image numbers (e.g., "1/10")
        infinigall: true,            // Enable infinite gallery loop
        navigation: true,            // Show prev/next arrows
        spinner: 'wave'              // Loading animation style
    });
</script>