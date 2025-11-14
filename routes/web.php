<?php

use App\Http\Controllers\PagesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PrimeLondonController;
use App\Http\Controllers\OuterPrimeLondonController;
use App\Http\Controllers\UltraLondonController;
use App\Http\Controllers\RepossessionsController;
use App\Http\Controllers\InterestRateController;
use App\Http\Controllers\MortgageApprovalController;
use App\Http\Controllers\UnemploymentController;
use App\Http\Controllers\MortgageCalcController;
use App\Http\Controllers\StampDutyController;
use App\Http\Controllers\EpcController;
use App\Http\Controllers\HpiDashboardController;
use App\Http\Controllers\NewOldController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\AffordabilityController;
use App\Http\Controllers\DeprivationController;


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
use App\Http\Controllers\AdminPostCodesController;

// Base Pages

Route::get('/', [PagesController::class, 'home'])->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');

// Affordability
Route::get('/affordability', [AffordabilityController::class, 'index'])->name('affordability.index');
Route::get('/affordability/show/{token}', [AffordabilityController::class, 'show'])->name('affordability.show');
Route::post('/affordability/calculate', [AffordabilityController::class, 'calculate'])->name('affordability.calculate');

Route::get('/property', [PropertyController::class, 'home'])->name('property.home');
Route::get('/property/search', [PropertyController::class, 'search'])->name('property.search');
Route::get('/property/show', [PropertyController::class, 'show'])->name('property.show');
Route::get('/property/prime-central-london', [PrimeLondonController::class, 'home'])->name('property.pcl');
Route::get('/property/outer-prime-london', [OuterPrimeLondonController::class, 'home'])->name('property.outer');
Route::get('/property/ultra-prime-central-london', [UltraLondonController::class, 'home'])->name('property.upcl');
Route::get('/epc', [EpcController::class, 'home'])->name('epc.home');
Route::get('/epc/search', [EpcController::class, 'search'])->name('epc.search');
// routes/web.php
Route::get('/epc/search_scotland', [\App\Http\Controllers\EpcController::class, 'searchScotland'])
    ->name('epc.search_scotland');
Route::get('/epc/{lmk}', [EpcController::class, 'show'])->name('epc.show');
Route::get('/hpi', [HpiDashboardController::class, 'index'])->name('hpi.home');
// New vs Existing (New/Old) dashboard
Route::get('/new-old', [NewOldController::class, 'index'])->name('newold.index');
Route::match(['get', 'post'], '/mortgage-calculator', [MortgageCalcController::class, 'index'])->name('mortgagecalc.index');

Route::get('/stamp-duty', [StampDutyController::class, 'index']);
Route::post('/stamp-duty/calc', [StampDutyController::class, 'calculate']);


Route::get('/interest-rates', [InterestRateController::class, 'home'])->name('interest.home');
Route::get('/unemployment', [UnemploymentController::class, 'index'])->name('unemployment.home');
Route::get('/inflation', [\App\Http\Controllers\InflationController::class, 'index'])->name('inflation.home');
Route::get('/wage-growth', [\App\Http\Controllers\WageGrowthController::class, 'index'])->name('wagegrowth.home');
Route::get('/hpi-overview', [HpiDashboardController::class, 'overview'])->name('hpi.overview');
Route::get('/economic-dashboard', [\App\Http\Controllers\EconomicDashboardController::class, 'index'])->name('economic.dashboard');
Route::get('/approvals', [MortgageApprovalController::class, 'home'])->name('mortgages.home');
Route::get('/repossessions/overview', [RepossessionsController::class, 'overview'])->name('repossessions.overview');
Route::get('/repossessions', [RepossessionsController::class, 'index'])->name('repossessions.index');

// Deprivation Routes
Route::get('/deprivation', [DeprivationController::class, 'index'])->name('deprivation.index');
Route::get('/deprivation/{lsoa21cd}', [DeprivationController::class, 'show'])->name('deprivation.show');
Route::get('/deprivation/scotland/{dz}', [\App\Http\Controllers\DeprivationController::class, 'showScotland'])->name('deprivation.scot.show');
Route::get('/deprivation/wales/{lsoa}', [DeprivationController::class, 'showWales'])->name('deprivation.wales.show');
Route::get('/deprivation/northern-ireland/{sa}', [DeprivationController::class, 'showNorthernIreland'])->name('deprivation.ni.show');

Route::resource('/blog', BlogController::class);

// Routes first protected by Auth

Route::middleware('auth')->group(function () {

    // Standard Routes that require login to access
    Route::resource('/profile', ProfileController::class);
    Route::post('/comments', [CommentsController::class, 'store'])->name('comments.store');

    // Protect the Dashboard routes behind both Auth and Can
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('can:Admin')
        ->group(function () {
            Route::resource('/', AdminController::class);
            Route::resource('users', AdminUserController::class);
            Route::resource('blog', AdminBlogController::class);
            Route::resource('postcodes', AdminPostCodesController::class);
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