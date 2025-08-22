<?php

use App\Http\Controllers\PagesController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RepossessionsController;
use App\Http\Controllers\InterestRateController;
use App\Http\Controllers\MortgageApprovalController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentsController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;


// 3rd Party packages 

use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\BlogPosts;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminBlogController;
use App\Http\Controllers\AdminUserController;

// Base Pages

Route::get('/', [PagesController::class, 'home'])->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');

Route::get('/property', [PropertyController::class, 'home'])->name('property.home');
Route::get('/property/search', [PropertyController::class, 'search'])->name('property.search');
Route::get('/property/show', [PropertyController::class, 'show'])->name('property.show');


Route::get('/interest-rates', [InterestRateController::class, 'home'])->name('interest.home');
Route::get('/approvals', [MortgageApprovalController::class, 'home'])->name('mortgages.home');
Route::get('/repossessions', [RepossessionsController::class, 'index'])->name('repossessions.index');

Route::resource('/blog', BlogController::class);

// Routes first protected by Auth

Route::middleware('auth')->group(function () {

    // Standard Routes that require login to access
    Route::resource('/profile', ProfileController::class);
    Route::post('/comments', [CommentsController::class, 'store'])->name('comments.store');

    // Protect the Dashboard routes behind both Auth and Can
    Route::prefix('admin')->group(function () {
        Route::resource('/', AdminController::class)->middleware('can:Admin');
        Route::resource('/users', AdminUserController::class)->middleware('can:Admin');
        Route::resource('/blog', AdminBlogController::class)->middleware('can:Admin');
    });

// Logout route to clear session.

Route::get('/logout', function(){
    Session::flush();
    Auth::logout();
    return Redirect::to("/");
});

});

// Sitemap by Spatie - Need to run generate-sitemap

Route::get('/generate-sitemap', function () {
    try {
        $sitemap = Sitemap::create()
            ->add(Url::create('/'))
            ->add(Url::create('/blog'))
            ->add(Url::create('/about'));

        $posts = BlogPosts::where('published', true)->get();

       Illuminate\Support\Facades\Log::info('Sitemap generation: blog post count', ['count' => $posts->count()]);

        if ($posts->isEmpty()) {
            return response('No blog posts found to add to sitemap.', 200);
        }

        foreach ($posts as $post) {
            $sitemap->add(
                Url::create("/blog/{$post->slug}")
                    ->setLastModificationDate($post->updated_at)
            );
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        return 'Sitemap generated!';
    } catch (\Exception $e) {
       Illuminate\Support\Facades\Log::error('Sitemap generation failed', ['error' => $e]);
        return response('Sitemap generation failed. Check logs.', 500);
    }
});

require __DIR__.'/auth.php';