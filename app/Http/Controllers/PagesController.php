<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        return view('pages.home');
    }

    /**
     * Static About page.
     */
    public function about()
    {
        return view('pages.about');
    }

}
