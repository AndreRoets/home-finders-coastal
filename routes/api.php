<?php

use App\Http\Controllers\CorexWebhookController;
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
