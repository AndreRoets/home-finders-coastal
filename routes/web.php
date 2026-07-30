<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PropertySeoController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Public\AgentController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\BranchController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\ListingController;
use App\Http\Controllers\Public\MediaController;
use App\Http\Controllers\Public\PropertyEnquiryController;
use App\Http\Controllers\SitemapController;
use App\Models\Page;
use App\Models\PageRedirect;
use App\Support\PageRoutes;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Marketing / admin panel (authenticated, login-only access)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');

    Route::get('properties/{id}/edit', [PropertySeoController::class, 'edit'])->name('properties.edit');
    Route::put('properties/{id}', [PropertySeoController::class, 'update'])->name('properties.update');

    // Image uploads for social share cards / marketing artwork.
    Route::post('media', [MediaUploadController::class, 'store'])->name('media.store');

    Route::get('marketing', [SiteSettingController::class, 'edit'])->name('marketing.edit');
    Route::put('marketing', [SiteSettingController::class, 'update'])->name('marketing.update');

    Route::resource('users', UserController::class)->except(['show']);
});

// Legacy starter-kit dashboard link now lands on the marketing panel.
Route::middleware(['auth'])->get('dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

/*
|--------------------------------------------------------------------------
| SEO endpoints
|--------------------------------------------------------------------------
*/
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Listing image proxy — trims the white border off letterboxed CoreX photos.
Route::get('media/trimmed', [MediaController::class, 'trimmed'])->name('media.trimmed');

// Admin-uploaded marketing images (og:image and friends), served publicly so
// social crawlers can fetch them.
Route::get('media/uploads/{file}', [MediaController::class, 'uploaded'])
    ->where('file', '[A-Za-z0-9._-]+')
    ->name('media.uploaded');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Public, database-slug-driven pages
|--------------------------------------------------------------------------
| Slugs are managed from the admin panel. Registered after the reserved
| routes above so a page slug can never shadow them. Old slugs are kept as
| 301 redirects to their page's current slug to preserve search rankings.
*/
try {
    $pages = Page::query()->get()->keyBy('key');
    $redirects = PageRedirect::query()->with('page')->get();
} catch (Throwable) {
    $pages = collect();
    $redirects = collect();
}

foreach ($redirects as $redirect) {
    if ($redirect->page) {
        Route::get(ltrim($redirect->old_slug, '/'), fn () => redirect($redirect->page->path(), $redirect->status_code));
    }
}

foreach (PageRoutes::actions() as $key => $action) {
    $slug = $pages->get($key)?->slug ?? $key;
    $uri = $slug === '/' ? '/' : ltrim($slug, '/');

    Route::get($uri, $action)->name($key);

    // The contact page also accepts a form submission at its own slug, rate
    // limited as a first line of defence against spam (the honeypot is second).
    if ($key === 'contact') {
        Route::post($uri, [ContactController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('contact.send');
    }
}

// Our Branches. CoreX-backed and gated behind the agency's show.branches
// feature toggle — the controller 404s when it is off.
Route::get('branches', [BranchController::class, 'index'])->name('branches');
Route::get('branches/{id}', [BranchController::class, 'show'])->name('branches.show');

// Bond repayment calculator — a static, client-side tool (no CoreX/backend).
Route::get('bond-calculator', fn () => Inertia::render('bond-calculator'))->name('bond-calculator');

// Privacy Policy — static, client-rendered legal page (footer-linked only).
Route::get('privacy-policy', fn () => Inertia::render('privacy-policy'))->name('privacy-policy');

// Property detail pages are dynamic (CoreX-backed), not CMS-managed.
Route::get('property/{idOrRef}', [ListingController::class, 'show'])->name('property.show');

// Property enquiry — emails the listing's agent(s) and pushes the lead to CoreX.
// Rate limited as a first line of defence against spam (honeypot is second).
Route::post('property/{idOrRef}/enquire', [PropertyEnquiryController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('property.enquire');

// Agent detail pages are dynamic (CoreX-backed), not CMS-managed. Registered as
// a sub-path of the agents index so it never collides with a managed page slug.
// {agent} is a name slug (e.g. "elize-reichel"); a bare numeric CoreX id still
// resolves too, so older "/agents/{id}" links keep working.
Route::get('agents/{agent}', [AgentController::class, 'show'])->name('agents.show');

// Article pages are dynamic (CoreX-backed), not CMS-managed. The /articles/
// prefix keeps the slug from colliding with a managed page slug.
Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
