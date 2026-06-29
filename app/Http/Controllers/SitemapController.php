<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Support\PageRoutes;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

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

        // Dynamic, CoreX-backed pages (properties and agents). A CoreX outage
        // must not break the sitemap, so the fetch fails soft to no extra URLs.
        try {
            $corex = app(CorexClient::class);

            foreach ($corex->listings() as $listing) {
                $xml .= $this->url(route('property.show', ListingMapper::slug($listing)));
            }

            foreach ($corex->agents() as $agent) {
                $id = Arr::get($agent, 'id');

                if ($id !== null && $id !== '') {
                    $xml .= $this->url(route('agents.show', $id));
                }
            }
        } catch (\Throwable) {
            // Leave the managed pages as the sitemap; the dynamic URLs are
            // simply omitted until CoreX is reachable again.
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * A bare `<url>` entry for a dynamic page (one with no managed metadata).
     */
    protected function url(string $loc): string
    {
        return "  <url>\n    <loc>".e($loc)."</loc>\n  </url>\n";
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
