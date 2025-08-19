<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlogPosts;
use App\Models\BlogCategories;
use App\Models\BlogTags;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Str;
use App\Services\ImageService;
use App\Http\Controllers\ContentImageController;

class BlogController extends Controller
{

    protected $imageService;

    // Constructor injection for ImageService
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the resource.
     */
public function index()
{
   if (isset($_GET['search'])) {
       $searchTerm = $_GET['search'];
        $posts = BlogPosts::where('published', true)
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('body', 'LIKE', '%' . $searchTerm . '%');
            })->simplePaginate(6);

       // Keep the search term in the pagination links
       $posts->appends(['search' => $searchTerm]);

   } elseif (isset($_GET['category'])) {
       $category = $_GET['category'];
       $posts = BlogPosts::GetCategories($category);  //Already Paginated in BlogPosts Model

       // Keep the category filter in the pagination links
       $posts->appends(['category' => $category]);

   } elseif (isset($_GET['tag'])) {
       $tag = $_GET['tag'];
       $posts = BlogPosts::GetTags($tag)->paginate(6)->onEachSide(1);

       // Keep the tag filter in the pagination links
       $posts->appends(['tag' => $tag]);

   } else {
       $posts = BlogPosts::with('BlogCategories', 'BlogTags', 'Users')
            ->where('published', true)
            ->orderBy('date', 'desc')
            ->simplePaginate(6);
   }

   $categories = BlogCategories::all();

   $popular_tags = DB::table('blog_post_tags')
       ->leftJoin('blog_tags', 'blog_tags.id', '=', 'blog_post_tags.tag_id')
       ->select('blog_post_tags.tag_id', 'name', DB::raw('count(*) as total'))
       ->groupBy('blog_post_tags.tag_id', 'name')
       ->orderBy('total', 'desc')
       ->limit(15)
       ->get();

   $unpublished = BlogPosts::where('published', false)->get();

   return view('blog.index', compact('posts', 'categories', 'popular_tags', 'unpublished'));
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('Admin');

        $categories = BlogCategories::all();

        return view('blog.create', compact('categories'));
    }

/**
 * Store a newly created resource in storage.
 */
public function store(Request $request, ImageService $imageService)
{
    Gate::authorize('Admin');

    // Prepare a new database entry for the blog post
    $page = new BlogPosts;

    $page->date          = $request->date;
    $page->title         = $request->title;
    $page->summary       = $request->summary;
    $page->slug          = Str::slug($page->title, '-');
    $page->body          = $request->body;
    $page->user_id       = Auth::user()->id;
    $page->categories_id = $request->category;

    // Process the featured image
    if ($request->hasFile('image')) {
        // This now returns the original image file name
        $originalImageName = $imageService->handleImageUpload($request->file('image'));
        // Store the original image file name in the blog post record
        $page->original_image = $originalImageName;
    }

    // Handle additional images for use in the editor (in-post images)
    $uploadedPaths = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            // Use optimizeAndSaveImage for editor images to create a single optimized version
            $imagePath = $imageService->optimizeAndSaveImage($image);
            $uploadedPaths[] = $imagePath;
        }
    }
    // Store the additional image paths in the 'images' column as JSON
    $page->images = json_encode($uploadedPaths);

    // Handle Gallery Images: process each image to store full size and a thumbnail (350x200)
    $galleryImages = [];
    if ($request->hasFile('gallery_images')) {
        foreach ($request->file('gallery_images') as $galleryImage) {
            // Process each gallery image using the ImageService
            // The handleGalleryImageUpload() should return an array like ['original' => ..., 'thumbnail' => ...]
            $result = $imageService->handleGalleryImageUpload($galleryImage);
            $galleryImages[] = $result;
        }
    }
    // Store the gallery images JSON data in the 'gallery_images' column
    $page->gallery_images = json_encode($galleryImages);

    // Check if the post is to be published
    $page->published = $request->has('published') ? 1 : 0;

    // Check whether the post is featured
    $page->featured = $request->has('featured') ? 1 : 0;

    // Save the post to the database
    $page->save();

    // Sync the tags to the post
    BlogTags::StoreTags($request->tags, $page->slug);

    return redirect()->action([BlogController::class, 'index'])
                     ->with('success', 'Post created successfully! Images are available for use.');
}

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        // Retrieve the post along with its related categories, user, and tags
        $page = BlogPosts::with('BlogCategories', 'users', 'blogTags')
                         ->where('slug', $slug)
                         ->firstOrFail();
    
        // Get the most recent pages (for sidebar or similar use)
        $recentPages = BlogPosts::orderBy('date', 'desc')->take(3)->get();
    
        // (Optional) Get featured posts if needed
        $featured = BlogPosts::where('featured', true)
                             ->orderBy('id', 'desc')
                             ->take(3)
                             ->get();
    
        // Process the gallery images, if any exist.
        // This will render the gallery partial and assign its HTML to $galleryHtml.
        $galleryHtml = '';
        if (!empty($page->gallery_images)) {
            $galleryImages = json_decode($page->gallery_images, true);
            $galleryHtml = view('partials.gallery', ['galleryImages' => $galleryImages])->render();
        }
    
        // Replace the {{gallery}} placeholder in the post's body with the rendered gallery HTML
        $page->body = str_replace('{{gallery}}', $galleryHtml, $page->body);
    
        // Prepare "previous" and "next" post queries by date (or by ID)
        $previousPage = BlogPosts::where('published', true)
                                ->where('date', '<', $page->date)
                                ->orderBy('date', 'desc')
                                ->first();

        $nextPage = BlogPosts::where('published', true)
                            ->where('date', '>', $page->date)
                            ->orderBy('date', 'asc')
                            ->first();
    
        return view('blog.show', compact('page', 'recentPages', 'featured', 'previousPage', 'nextPage'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        Gate::authorize('Admin');

        $page = BlogPosts::find($id);
        $categories = BlogCategories::all();
        $split_tags = BlogTags::TagsForEdit($id);

        return view('blog.edit', compact('page', 'categories', 'split_tags'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id, ImageService $imageService)
    {
        Gate::authorize('Admin');

        // Retrieve the existing post from the database
        $page = BlogPosts::findOrFail($id);

        // Update the post fields
        $page->date          = $request->date;
        $page->title         = $request->title;
        $page->summary       = $request->summary;
        $page->slug          = Str::slug($page->title, '-');
        $page->body          = $request->body;
        $page->user_id       = Auth::user()->id;
        $page->categories_id = $request->category;

        // Define your uploads path (this should match your ImageService)
        $uploadPath = '/assets/images/uploads/';

        // Process the featured image
        if ($request->hasFile('image')) {
            // Delete the old images (original and resized)
            $oldImageName = $page->original_image;
            if ($oldImageName) {
                $originalPath = $uploadPath . $oldImageName;
                $smallPath    = $uploadPath . 'small_' . $oldImageName;
                $mediumPath   = $uploadPath . 'medium_' . $oldImageName;
                $largePath    = $uploadPath . 'large_' . $oldImageName;

                // Delete all existing versions of the featured image
                $imageService->deleteImage([$originalPath, $smallPath, $mediumPath, $largePath]);
            }

            // Upload the new featured image and store its filename
            $newImageName = $imageService->handleImageUpload($request->file('image'));
            $page->original_image = $newImageName;
        }

        // Process additional in-post images (optimized versions)
        $uploadedPaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $imageService->optimizeAndSaveImage($image);
                $uploadedPaths[] = $imagePath;
            }
        }
        // Merge new in-post images with any existing ones
        $existingImages = json_decode($page->images) ?? [];
        $updatedImages = array_merge($existingImages, $uploadedPaths);
        $page->images = json_encode($updatedImages);

        // Process gallery images
        if ($request->hasFile('gallery_images')) {
            // Optionally, if you want to replace the entire gallery,
            // you can have a flag (for example, 'delete_gallery') in your form.
            if ($request->has('delete_gallery') && $request->delete_gallery == true) {
                $existingGalleryImages = json_decode($page->gallery_images, true) ?? [];
                foreach ($existingGalleryImages as $galleryImage) {
                    // Each gallery image contains an 'original' and a 'thumbnail'
                    $originalGalleryPath = '/assets/images/uploads/galleries/' . $galleryImage['original'];
                    $thumbnailGalleryPath = '/assets/images/uploads/galleries/' . $galleryImage['thumbnail'];
                    $imageService->deleteImage([$originalGalleryPath, $thumbnailGalleryPath]);
                }
                // Reset existing gallery images if replacing
                $existingGalleryImages = [];
            } else {
                $existingGalleryImages = json_decode($page->gallery_images, true) ?? [];
            }

            $newGalleryImages = [];
            foreach ($request->file('gallery_images') as $galleryImage) {
                // Process each gallery image with the ImageService
                $result = $imageService->handleGalleryImageUpload($galleryImage);
                $newGalleryImages[] = $result;
            }
            // Merge the new gallery images with the existing ones (if any)
            $updatedGalleryImages = array_merge($existingGalleryImages, $newGalleryImages);
            $page->gallery_images = json_encode($updatedGalleryImages);
        }

        // Set publication and featured options
        $page->published = $request->has('published') ? 1 : 0;
        $page->featured  = $request->has('featured') ? 1 : 0;

        // Save the updated post
        $page->save();

        // Sync the tags
        BlogTags::StoreTags($request->tags, $page->slug);

        return back()->with('success', 'Post updated successfully with new images!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, ImageService $imageService)
    {
        Gate::authorize('Admin');

        // Retrieve the post by ID
        $page = BlogPosts::findOrFail($id);

        // Define the uploads folder path (should match what you use in your ImageService)
        $uploadPath = '/assets/images/uploads/';

        // Construct the full file paths for the featured image (original and resized versions)
        $originalImagePath = $uploadPath . $page->original_image;
        $smallImagePath    = $uploadPath . 'small_' . $page->original_image;
        $mediumImagePath   = $uploadPath . 'medium_' . $page->original_image;
        $largeImagePath    = $uploadPath . 'large_' . $page->original_image;

        // Delete the featured images
        $imageService->deleteImage([
            $originalImagePath,
            $smallImagePath,
            $mediumImagePath,
            $largeImagePath
        ]);

        // Delete additional in-post images stored in the 'images' JSON field, if they exist
        $additionalImages = json_decode($page->images);
        if ($additionalImages) {
            foreach ($additionalImages as $imagePath) {
                $imageService->deleteImage([$imagePath]);
            }
        }

        // Delete gallery images stored in the 'gallery_images' JSON field, if they exist
        $galleryImages = json_decode($page->gallery_images, true);
        if ($galleryImages) {
            foreach ($galleryImages as $galleryImage) {
                // Each gallery image is expected to be an array with keys 'original' and 'thumbnail'
                $originalGalleryPath  = '/assets/images/uploads/galleries/' . $galleryImage['original'];
                $thumbnailGalleryPath = '/assets/images/uploads/galleries/' . $galleryImage['thumbnail'];
                $imageService->deleteImage([$originalGalleryPath, $thumbnailGalleryPath]);
            }
        }

        // Delete the post from the database
        $page->delete();

        return back()->with('success', 'Page deleted successfully!');
    }

}
