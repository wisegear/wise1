<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BlogPosts;

/**
 * PagesController
 *
 * Responsible for the public pages (home/about). The home action can:
 *  - Perform a postcode search with sorting + pagination
 *  - Compute/cached site-wide aggregates for charts (sales counts, avg prices, prime/ultra prime slices)
 */
class PagesController extends Controller
{

    public function home()
    {
        // Get the 4 most recent blog posts
        $posts = BlogPosts::where('published', true)->orderBy('date', 'desc')->take(4)->get();

        return view('pages.home', compact('posts'));
    }

    /**
     * Static About page.
     */
    public function about()
    {
        return view('pages.about');
    }

}
