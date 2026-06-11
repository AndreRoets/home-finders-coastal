<?php

namespace App\Support;

use App\Http\Controllers\Public\AgentController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\ListingController;

class PageRoutes
{
    /**
     * Map a manageable page key to the route action that renders it.
     * Keys here must match the `key` column on the pages table.
     *
     * @return array<string, mixed>
     */
    public static function actions(): array
    {
        return [
            'home' => [ListingController::class, 'home'],
            'for-sale' => [ListingController::class, 'forSale'],
            'to-rent' => [ListingController::class, 'toRent'],
            'hfc-exclusive' => [ListingController::class, 'exclusive'],
            'sold' => [ListingController::class, 'sold'],
            'agents' => [AgentController::class, 'index'],
            'contact' => [ContactController::class, 'show'],
        ];
    }

    /**
     * Slugs that may never be used by a page because they collide with
     * reserved application routes (admin panel, auth, SEO endpoints).
     *
     * @return list<string>
     */
    public static function reservedSlugs(): array
    {
        return [
            'admin', 'login', 'logout', 'register', 'dashboard',
            'settings', 'forgot-password', 'reset-password', 'verify-email',
            'confirm-password', 'property', 'branches', 'bond-calculator', 'privacy-policy', 'media', 'sitemap.xml', 'robots.txt', 'up',
        ];
    }
}
