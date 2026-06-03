<?php

use App\Http\Controllers\Public\AgentController;
use App\Http\Controllers\Public\ListingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ListingController::class, 'home'])->name('home');
Route::get('/home/luxe', [ListingController::class, 'homeLuxe'])->name('home.luxe');
Route::get('/home/vibrant', [ListingController::class, 'homeVibrant'])->name('home.vibrant');
Route::get('/home/minimal', [ListingController::class, 'homeMinimal'])->name('home.minimal');
Route::get('/home/portal', [ListingController::class, 'homePortal'])->name('home.portal');
Route::get('/home/motion', [ListingController::class, 'homeMotion'])->name('home.motion');
Route::get('/home/coastal', [ListingController::class, 'homeCoastal'])->name('home.coastal');

Route::get('/for-sale', [ListingController::class, 'forSale'])->name('for-sale');
Route::get('/to-rent', [ListingController::class, 'toRent'])->name('to-rent');
Route::get('/hfc-exclusive', [ListingController::class, 'exclusive'])->name('hfc-exclusive');
Route::get('/sold', [ListingController::class, 'sold'])->name('sold');
Route::get('/property/{idOrRef}', [ListingController::class, 'show'])->name('property.show');
Route::get('/agents', [AgentController::class, 'index'])->name('agents');

Route::get('/contact', function () {
    return Inertia::render('contact');
})->name('contact');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
