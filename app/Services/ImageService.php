<?php

namespace App\Services;

use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageService
{
    public function handleImageUpload($image)
    {
        $path = '/assets/images/uploads/';

        // Generate a unique image name without any prefix for the original image
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                   . '_' . time() . '.' . $image->getClientOriginalExtension();

        // Define file names for the resized images
        $smallImageName  = 'small_'  . $imageName;
        $mediumImageName = 'medium_' . $imageName;
        $largeImageName  = 'large_'  . $imageName;

        // Move the original image to the uploads folder using the image name without prefix
        $image->move(public_path($path), $imageName);

        // Create resized versions based on the original image

        // Small image: 350x200, 50% quality
        Image::read(public_path($path . $imageName))
             ->cover(350, 200, 'center')
             ->save(public_path($path . $smallImageName), 50);

        // Medium image: 800x300, 75% quality
        Image::read(public_path($path . $imageName))
             ->cover(800, 300, 'center')
             ->save(public_path($path . $mediumImageName), 75);

        // Large image: 1200x400, 75% quality
        Image::read(public_path($path . $imageName))
             ->cover(1200, 400, 'center')
             ->save(public_path($path . $largeImageName), 75);

        // Return only the original image's file name (not the full path)
        return $imageName;
    }

    public function deleteImage($imagePaths)
    {
        // If $imagePaths is an array of multiple image paths
        if (is_array($imagePaths)) {
            foreach ($imagePaths as $imagePath) {
                if (!empty($imagePath) && file_exists(public_path($imagePath))) {
                    unlink(public_path($imagePath));  // Delete each file
                }
            }
        } elseif (is_string($imagePaths)) {
            // If $imagePaths is a single string, delete that one image
            if (!empty($imagePaths) && file_exists(public_path($imagePaths))) {
                unlink(public_path($imagePaths));
            }
        }
    }    

    public function optimizeAndSaveImage($image, $path = '/assets/images/uploads/')
    {
        // Generate a unique image name
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();

        // Create the full path for storing the optimized image
        $optimizedPath = $path . $imageName;

        // Ensure the path exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        Image::read($image->getRealPath())
            ->save(public_path($optimizedPath), 50);  // Save with 50% quality to reduce file size

        return $optimizedPath;
    }

    public function handleLinkImageUpload($image)
    {
        $path = '/assets/images/uploads/';
        
        // Generate a unique image name
        $imageName = 'link_' . pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.' . $image->getClientOriginalExtension();
        $fullImagePath = public_path($path . $imageName);
    
        // Ensure the directory exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }
    
        // Read, resize, crop, and save the image at 200x200 pixels
        Image::read($image->getRealPath())
            ->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->crop(200, 200)
            ->save($fullImagePath, 75);
    
        return $imageName; // Return only the image name
    }

    /**
     * Handles uploading gallery images.
     *
     * Saves the original image to /assets/images/uploads/galleries and creates a 350x200 thumbnail.
     * Returns an array containing the filenames for both the original and thumbnail.
     */
    public function handleGalleryImageUpload($image)
    {
        $path = '/assets/images/uploads/galleries/';

        // Generate a unique image name
        $imageName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                   . '_' . time() . '.' . $image->getClientOriginalExtension();

        // Ensure the directory exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        // Define paths for the original and thumbnail images
        $originalPath = $path . $imageName;
        $thumbnailPath = $path . 'thumbnail_' . $imageName;

        // Move the original image to the destination directory
        $image->move(public_path($path), $imageName);

        // Create the thumbnail (350x200)
        Image::read(public_path($originalPath))
            ->resize(350, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->crop(350, 200)
            ->save(public_path($thumbnailPath), 70);

        // Return an array with both filenames
        return [
            'original'  => $imageName,
            'thumbnail' => 'thumbnail_' . $imageName,
        ];
    }
}