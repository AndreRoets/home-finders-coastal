<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * /sitemap.xml is what Google crawls. It lists the indexable managed pages, the
 * standalone pages, and every CoreX-backed property, agent, branch and article.
 * A CoreX outage must degrade the sitemap, never break it.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    /** These tests control the agency payload (the branches toggle lives on it). */
    protected bool $fakeAgency = false;

    protected function fakeCorex(bool $branches = true): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => [
                'name' => 'Home Finders Coastal',
                'show' => ['agents' => true, 'listings' => true, 'branches' => $branches],
            ]]),
            '*/branches*' => Http::response([
                'data' => [['id' => 12, 'trading_name' => 'Coastal Realty Margate']],
                'meta' => ['last_page' => 1],
            ]),
            '*/listings*' => Http::response([
                'data' => [['id' => 44, 'title' => 'Sea Facing Home', 'updated_at' => '2026-08-01T09:00:00+02:00']],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents*' => Http::response([
                'data' => [['id' => 7, 'name' => 'Thandi Mbeki']],
                'meta' => ['last_page' => 1],
            ]),
            '*/articles*' => Http::response([
                'data' => [['id' => 12, 'title' => 'Bond Approval', 'slug' => 'bond-approval']],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    protected function page(array $attributes = []): Page
    {
        return Page::query()->create(array_merge([
            'key' => 'contact',
            'name' => 'Contact Us',
            'slug' => 'contact',
            'is_active' => true,
        ], $attributes));
    }

    public function test_it_lists_managed_static_and_dynamic_urls(): void
    {
        $this->fakeCorex();
        $this->page(['sitemap_frequency' => 'monthly', 'sitemap_priority' => '0.7']);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');

        $xml = $response->getContent();

        $this->assertStringContainsString('<loc>'.url('/contact').'</loc>', $xml);
        $this->assertStringContainsString('<changefreq>monthly</changefreq>', $xml);
        $this->assertStringContainsString('<priority>0.7</priority>', $xml);
        $this->assertStringContainsString('<loc>'.route('bond-calculator').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('privacy-policy').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('branches').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('branches.show', 12).'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('property.show', 'sea-facing-home-44').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('agents.show', 'thandi-mbeki').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('articles.show', 'bond-approval').'</loc>', $xml);

        // The listing's CoreX timestamp becomes its lastmod.
        $this->assertStringContainsString('<lastmod>2026-08-01T09:00:00+02:00</lastmod>', $xml);
    }

    public function test_it_omits_inactive_and_noindex_pages(): void
    {
        $this->fakeCorex();
        $this->page(['key' => 'contact', 'slug' => 'contact', 'is_active' => false]);
        $this->page(['key' => 'sold', 'name' => 'Sold', 'slug' => 'sold', 'robots_index' => false]);
        $this->page(['key' => 'agents', 'name' => 'Agents', 'slug' => 'meet-the-team']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('<loc>'.url('/contact').'</loc>', $xml);
        $this->assertStringNotContainsString('<loc>'.url('/sold').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.url('/meet-the-team').'</loc>', $xml);
    }

    public function test_branch_urls_are_omitted_when_the_feature_is_off(): void
    {
        $this->fakeCorex(branches: false);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString('<loc>'.route('branches').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('property.show', 'sea-facing-home-44').'</loc>', $xml);
    }

    public function test_a_corex_outage_still_yields_a_valid_sitemap(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $this->page();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.url('/contact').'</loc>', $xml);
        $this->assertStringContainsString('</urlset>', $xml);
    }

    public function test_robots_txt_points_at_the_sitemap_by_default(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
    }

    public function test_robots_txt_uses_the_admin_override(): void
    {
        SiteSetting::current()->update(['robots_txt' => "User-agent: *\nDisallow: /"]);

        $this->get('/robots.txt')->assertOk()->assertSee("User-agent: *\nDisallow: /", false);
    }
}
