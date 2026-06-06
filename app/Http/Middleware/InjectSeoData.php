<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class InjectSeoData
{
    /**
     * Share the resolved per-page SEO meta and site-wide marketing/analytics
     * settings with the root Blade view so they can be rendered server-side
     * (which is what search-engine and social crawlers read).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $isAdminArea = $this->isAdminArea($routeName);

        // Degrade gracefully if the marketing tables aren't migrated yet
        // (fresh installs, or tests that don't touch the database).
        try {
            $page = $isAdminArea
                ? null
                : Page::query()->where('key', $routeName)->first();

            $settings = SiteSetting::current();
        } catch (\Throwable) {
            $page = null;
            $settings = new SiteSetting;
        }

        View::share('seoPage', $page);
        View::share('siteSettings', $settings);
        // Analytics/tags only fire on the public site, never the admin panel or auth screens.
        View::share('injectAnalytics', ! $isAdminArea);

        return $next($request);
    }

    /**
     * Admin panel, auth and settings screens should not be tracked or
     * decorated with public SEO meta.
     */
    protected function isAdminArea(?string $routeName): bool
    {
        if ($routeName === null) {
            return true;
        }

        foreach (['admin.', 'login', 'logout', 'register', 'password.', 'verification.', 'settings.', 'dashboard'] as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
