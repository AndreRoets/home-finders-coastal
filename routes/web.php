<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::get('/for-sale', function () {
    return Inertia::render('for-sale');
})->name('for-sale');

Route::get('/to-rent', function () {
    return Inertia::render('to-rent');
})->name('to-rent');

Route::get('/hfc-exclusive', function () {
    return Inertia::render('hfc-exclusive');
})->name('hfc-exclusive');

Route::get('/sold', function () {
    return Inertia::render('sold');
})->name('sold');

Route::get('/agents', function () {
    return Inertia::render('agents');
})->name('agents');

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
