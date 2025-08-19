<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ImageService;
use App\Models\BlogPosts;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ContentImageController extends Controller
{
    protected $imageService;

    // Inject the ImageService
    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Handle image uploads for any content type (BlogPost, Article, etc.)
     */
    public function uploadImages(Request $request)
    {
        try {
            \Log::info('Request received for image upload: ', $request->all()); // Log incoming request data
    
            // Check if the request contains files
            if (!$request->hasFile('images')) {
                \Log::error('No images found in the request.');
                return response()->json(['success' => false, 'error' => 'No images found in the request'], 400);
            }
    
            \Log::info('Files in request: ', $request->file('images'));
    
            // Validate the uploaded images
            $request->validate([
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
    
            \Log::info('Validation passed.');
    
            $uploadedPaths = [];
    
            foreach ($request->file('images') as $image) {
                \Log::info('Processing image: ' . $image->getClientOriginalName());
    
                // Store the image and get its path
                $imagePath = $this->imageService->optimizeAndSaveImage($image);
                $uploadedPaths[] = $imagePath;
    
                \Log::info('Image stored at: ' . $imagePath);
            }
    
            \Log::info('Images uploaded successfully.');
    
            return response()->json(['success' => true, 'images' => $uploadedPaths]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error: ', $e->errors());
            return response()->json(['success' => false, 'error' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Server error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve content model based on type and ID.
     */
    private function getContentModel($content_type, $content_id)
    {
        switch ($content_type) {
            case 'blog':
                return BlogPosts::find($content_id);
            default:
                return null;
        }
    }
}
