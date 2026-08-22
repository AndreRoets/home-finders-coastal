<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\Corex\CorexClient;
use App\Support\SitemapBuilder;
use Inertia\Inertia;
use Inertia\Response;

class SitemapController extends Controller
{
    /**
     * How many URLs of a section are listed in the panel. Sections such as
     * properties can run to thousands of entries; the full set always lives in
     * the XML itself, which is one click away.
     */
    protected const PREVIEW_LIMIT = 50;

    public function __construct(protected SitemapBuilder $sitemap, protected CorexClient $corex) {}

    /**
     * The sitemap overview: what Google will find at /sitemap.xml, whether
     * robots.txt points at it, and how to submit it to Search Console.
     */
    public function index(): Response
    {
        $sections = array_map(fn (array $section) => [
            'key' => $section['key'],
            'label' => $section['label'],
            'description' => $section['description'],
            'dynamic' => $section['dynamic'],
            'count' => count($section['urls']),
            'urls' => array_slice($section['urls'], 0, self::PREVIEW_LIMIT),
        ], $this->sitemap->sections());

        return Inertia::render('admin/sitemap', [
            'sitemapUrl' => route('sitemap'),
            'robotsUrl' => route('robots'),
            'robotsReferencesSitemap' => $this->sitemap->robotsReferencesSitemap(),
            'searchConsoleVerified' => filled(SiteSetting::current()->google_search_console_verification),
            // CoreX feeds the property/agent/branch/article URLs. When it is
            // unreachable those sections read as empty, so say so rather than
            // letting an admin think the listings fell out of the sitemap.
            'corexAvailable' => $this->corex->ping(),
            'sections' => $sections,
            'total' => $this->sitemap->total(),
            'previewLimit' => self::PREVIEW_LIMIT,
        ]);
    }
}
