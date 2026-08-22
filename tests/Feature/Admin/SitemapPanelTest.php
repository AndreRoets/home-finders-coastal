<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The admin Sitemap panel shows exactly what /sitemap.xml advertises to Google,
 * plus whether robots.txt and Search Console are wired up.
 */
class SitemapPanelTest extends TestCase
{
    use RefreshDatabase;

    protected bool $fakeAgency = false;

    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    protected function fakeCorex(): void
    {
        Http::fake([
            '*/ping*' => Http::response(['ok' => true]),
            '*/agency*' => Http::response(['data' => ['show' => ['branches' => false]]]),
            '*/listings*' => Http::response([
                'data' => [['id' => 44, 'title' => 'Sea Facing Home']],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents*' => Http::response([
                'data' => [['id' => 7, 'name' => 'Thandi Mbeki']],
                'meta' => ['last_page' => 1],
            ]),
            '*/articles*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);
    }

    public function test_guests_cannot_reach_the_sitemap_panel(): void
    {
        $this->get('/admin/sitemap')->assertRedirect('/login');
    }

    public function test_it_summarises_the_sitemap(): void
    {
        $this->actingAsAdmin();
        $this->fakeCorex();

        Page::query()->create([
            'key' => 'contact',
            'name' => 'Contact Us',
            'slug' => 'contact',
            'is_active' => true,
        ]);

        $this->get('/admin/sitemap')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/sitemap')
                ->where('sitemapUrl', route('sitemap'))
                ->where('robotsReferencesSitemap', true)
                ->where('searchConsoleVerified', false)
                ->where('corexAvailable', true)
                ->has('sections', 6)
                ->where('sections.0.key', 'pages')
                ->where('sections.0.count', 1)
                ->where('sections.0.urls.0.loc', url('/contact'))
                ->where('sections.3.key', 'properties')
                ->where('sections.3.count', 1)
                // 1 managed + 2 standalone + 0 branches + 1 property + 1 agent.
                ->where('total', 5)
            );
    }

    public function test_it_reports_when_corex_cannot_be_reached(): void
    {
        $this->actingAsAdmin();
        Http::fake(['*' => Http::response('', 500)]);

        $this->get('/admin/sitemap')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('corexAvailable', false)
                ->where('sections.3.key', 'properties')
                ->where('sections.3.dynamic', true)
                ->where('sections.3.count', 0)
                // The CMS-driven sections still stand on their own.
                ->where('sections.1.key', 'static')
                ->where('sections.1.count', 2)
            );
    }

    public function test_it_warns_when_robots_txt_omits_the_sitemap(): void
    {
        $this->actingAsAdmin();
        $this->fakeCorex();

        SiteSetting::current()->update([
            'robots_txt' => "User-agent: *\nAllow: /",
            'google_search_console_verification' => 'abc123',
        ]);

        $this->get('/admin/sitemap')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('robotsReferencesSitemap', false)
                ->where('searchConsoleVerified', true)
            );
    }
}
