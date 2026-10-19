<?php

use App\Http\Controllers\Public\MediaController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InjectSeoData;
use App\Http\Middleware\RedirectIndexPhp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Image routes deliberately skip the `web` group: no session, cookies
            // or Inertia shared props per photo, and no Set-Cookie header, so the
            // responses are cacheable by nginx and browsers.
            // Listing image proxy — trims the white border off letterboxed CoreX photos.
            Route::get('media/trimmed', [MediaController::class, 'trimmed'])->name('media.trimmed');

            // Admin-uploaded marketing images (og:image and friends), served publicly
            // so social crawlers can fetch them.
            Route::get('media/uploads/{file}', [MediaController::class, 'uploaded'])
                ->where('file', '[A-Za-z0-9._-]+')
                ->name('media.uploaded');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(RedirectIndexPhp::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            InjectSeoData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
