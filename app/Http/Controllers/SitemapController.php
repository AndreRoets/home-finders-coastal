<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\PageRoutes;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * The XML sitemap, generated from the active managed pages.
     */
    public function index(): Response
    {
        $actions = PageRoutes::actions();

        $pages = Page::query()
            ->where('is_active', true)
            ->whereIn('key', array_keys($actions))
            ->get();

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($pages as $page) {
            $loc = url($page->path());
            $lastmod = optional($page->updated_at)->toAtomString();

            $xml .= "  <url>\n";
            $xml .= '    <loc>'.e($loc)."</loc>\n";

            if ($lastmod) {
                $xml .= '    <lastmod>'.$lastmod."</lastmod>\n";
            }

            $xml .= '    <changefreq>'.e($page->sitemap_frequency)."</changefreq>\n";
            $xml .= '    <priority>'.e($page->sitemap_priority)."</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * robots.txt — admin-managed, falling back to a sane default that points
     * crawlers at the sitemap and keeps the admin panel out of the index.
     */
    public function robots(): Response
    {
        $settings = SiteSetting::current();

        $body = trim((string) $settings->robots_txt) !== ''
            ? $settings->robots_txt
            : implode("\n", [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /settings',
                'Allow: /',
                '',
                'Sitemap: '.url('/sitemap.xml'),
            ]);

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }
}
