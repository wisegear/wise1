<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'comment_text' => 'required|string|max:500',
            'commentable_id' => 'required|integer',
            'commentable_type' => 'required|string',
        ]);

        // Store the new comment
        Comment::create([
            'comment_text' => $request->input('comment_text'),
            'commentable_id' => $request->input('commentable_id'),
            'commentable_type' => $request->input('commentable_type'),
            'user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Comment added successfully!');
    }
}
