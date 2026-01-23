<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
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

        // Get stats from cache only (warmed by home:stats-warm command)
        $stats = Cache::get('homepage_stats', [
            'property_records' => 0,
            'uk_avg_price' => 0,
            'uk_avg_rent' => 0,
            'bank_rate' => 0,
            'epc_count' => 0,
        ]);

        $totalStress = Cache::get('eco:total_stress');
        if (is_null($totalStress)) {
            $totalStress = Cache::get('eco:total_stress_persist');
        }

        return view('pages.home', compact('posts', 'stats', 'totalStress'));
    }

    /**
     * Static About page.
     */
    public function about()
    {
        return view('pages.about');
    }

}
