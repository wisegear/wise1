<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPostTags extends Model
{
    use HasFactory;
    protected $table = 'blog_post_tags';
    public $timestamps = false;
}
