<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected function contactPage(array $attributes = []): Page
    {
        return Page::query()->create(array_merge([
            'key' => 'contact',
            'name' => 'Contact Us',
            'slug' => 'contact',
        ], $attributes));
    }

    public function test_page_meta_is_rendered_server_side(): void
    {
        $this->contactPage([
            'meta_title' => 'Talk to HFC',
            'meta_description' => 'Reach the coastal team today.',
            'og_image' => 'https://example.com/og.jpg',
        ]);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('Reach the coastal team today.', false);
        $response->assertSee('<meta property="og:image" content="https://example.com/og.jpg">', false);
        $response->assertSee('<link rel="canonical"', false);
    }

    public function test_noindex_directive_is_emitted_when_indexing_is_disabled(): void
    {
        $this->contactPage(['robots_index' => false, 'robots_follow' => true]);

        $this->get('/contact')->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public function test_google_analytics_and_tag_manager_are_injected_on_public_pages(): void
    {
        $this->contactPage();
        SiteSetting::current()->update([
            'ga4_measurement_id' => 'G-TEST12345',
            'gtm_container_id' => 'GTM-TEST99',
        ]);

        $response = $this->get('/contact');

        $response->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST12345', false);
        $response->assertSee("'config', 'G-TEST12345'", false);
        $response->assertSee('GTM-TEST99', false);
    }

    public function test_analytics_are_not_injected_in_the_admin_panel(): void
    {
        SiteSetting::current()->update(['ga4_measurement_id' => 'G-TEST12345']);
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertDontSee('gtag/js?id=G-TEST12345', false);
    }

    public function test_sitemap_lists_active_pages(): void
    {
        $this->contactPage(['slug' => 'contact', 'is_active' => true]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee('/contact', false);
    }

    public function test_robots_txt_falls_back_to_a_sensible_default(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('Sitemap:', false);
        $response->assertSee('Disallow: /admin', false);
    }

    public function test_admin_managed_robots_txt_is_served(): void
    {
        SiteSetting::current()->update(['robots_txt' => "User-agent: *\nDisallow: /private"]);

        $this->get('/robots.txt')->assertSee('Disallow: /private', false);
    }
}
