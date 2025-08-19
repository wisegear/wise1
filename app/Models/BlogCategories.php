<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategories extends Model
{
    use HasFactory;
    protected $table = 'blog_categories';
    public $timestamps = false;

    public function BlogPosts() {
        return $this->hasMany(BlogPosts::class, 'categories_id', 'id');
    }
}
