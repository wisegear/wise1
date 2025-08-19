<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BlogPosts;

class BlogTags extends Model
{

    protected $fillable = [
      'name',
    ];

    use HasFactory;
    protected $table = 'blog_tags';
    public $timestamps = false;

    // Define relationship to BlogTags (many-to-many relationship)
    public function blogTags()
    {
        return $this->belongsToMany(BlogTags::class);
    }

    public function blogPosts()
    {
        return $this->belongsToMany(BlogPosts::class, 'blog_post_tags');  // Use the correct pivot table name if different
    }

    public static function storeTags($post_tags, $slug)
    {
        // Explode tags, removing the hyphen.
        $tags = explode('-', $post_tags);
    
        // Find the post by slug and detach any existing tags
        $blog_post = BlogPosts::where('slug', $slug)->first();
        $blog_post->blogTags()->detach();
    
        // Loop through each tag, create it if it doesn't exist, and attach it to the post
        foreach ($tags as $tag) {
            if (!empty($tag)) {
                // Use firstOrCreate to find the tag or create it if it doesn't exist
                $tagModel = BlogTags::firstOrCreate(['name' => $tag]);
    
                // Attach the tag to the post
                $blog_post->blogTags()->attach($tagModel->id);
            }
        }
    }   
      
    public static function TagsForEdit($id)
    {
        // Find the blog post by ID and pluck the tag names
        $post = BlogPosts::findOrFail($id);
    
        // Use pluck to get an array of tag names and then implode them with a hyphen separator
        return $post->blogTags()->pluck('name')->implode('-');
    }
}