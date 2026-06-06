<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Marketing panel overview: page count, user count and which Google
     * integrations are still missing a value.
     */
    public function index(): Response
    {
        $settings = SiteSetting::current();

        $integrations = [
            'ga4' => filled($settings->ga4_measurement_id),
            'gtm' => filled($settings->gtm_container_id),
            'search_console' => filled($settings->google_search_console_verification),
            'google_ads' => filled($settings->google_ads_conversion_id),
        ];

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'pages' => Page::query()->count(),
                'users' => User::query()->count(),
                'integrations' => $integrations,
                'integrationsConfigured' => count(array_filter($integrations)),
                'integrationsTotal' => count($integrations),
            ],
        ]);
    }
}
