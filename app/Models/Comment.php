<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    protected $fillable = ['comment_text', 'user_id', 'commentable_id', 'commentable_type'];

    // Define the polymorphic relationship
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

