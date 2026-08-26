<?php

use App\Http\Controllers\CorexWebhookController;
use App\Http\Controllers\ListingStatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Stateless, CSRF-exempt routes. The CoreX webhook lands here (rather than
| web.php) so deliveries don't need a session or CSRF token. The full,
| externally-registered URL is /api/corex-webhook.
|
*/

Route::post('/corex-webhook', [CorexWebhookController::class, 'handle'])
    ->name('corex.webhook');

// Browser-reported listing interactions (gallery opens, contact clicks,
// shares), sent as a fire-and-forget beacon from the property page and pushed
// on to CoreX in batches by `corex:push-stats`. Throttled because it is public
// and unauthenticated; the payload is validated against a strict allowlist.
Route::post('/listing-events', [ListingStatController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('listing-events.store');
