<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Public\AgentController;
use App\Http\Controllers\Public\ArticleController;
use App\Http\Controllers\Public\ListingController;
use App\Http\Controllers\SitemapController;
use App\Models\Page;
use App\Models\PageRedirect;
use App\Support\PageRoutes;
use Illuminate\Support\Facades\Route;

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
}

// Property detail pages are dynamic (CoreX-backed), not CMS-managed.
Route::get('property/{idOrRef}', [ListingController::class, 'show'])->name('property.show');

// Agent detail pages are dynamic (CoreX-backed), not CMS-managed. Registered as
// a sub-path of the agents index so it never collides with a managed page slug.
Route::get('agents/{id}', [AgentController::class, 'show'])->name('agents.show');

// Article pages are dynamic (CoreX-backed), not CMS-managed. The /articles/
// prefix keeps the slug from colliding with a managed page slug.
Route::get('articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
