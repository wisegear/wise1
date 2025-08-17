<?php

use App\Http\Controllers\PagesController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RepossessionsController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\InterestRateController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PagesController::class, 'home'])->name('home');
Route::get('/about', [PagesController::class, 'about'])->name('about');
Route::get('/property', [PropertyController::class, 'show'])->name('property.show');
Route::get('/sales', [SalesController::class, 'home'])->name('sales.home');
Route::get('/interest-rates', [InterestRateController::class, 'home'])->name('interest.home');

Route::get('/repossessions', [RepossessionsController::class, 'index'])
     ->name('repossessions.index');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
