<?php

namespace Tests\Feature\Public;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Server-side SEO: the client title matches the server title, Search Console
 * verification, robots.txt, JSON-LD, canonical URLs and social meta.
 */
class SeoFixesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $fakeAgency = false;

    /**
     * @return array<string, mixed>
     */
    protected function agency(): array
    {
        return [
            'name' => 'Home Finders Coastal',
            'trading_name' => 'HFC Realty',
            'logo_url' => 'https://corex.test/logo.png',
            'contact' => ['phone' => '039 315 0000', 'email' => 'info@hfc.test', 'address' => '1 Marine Drive, Margate'],
            'social' => ['facebook' => 'hfcoastal'],
            'show' => ['agents' => true, 'listings' => true, 'branches' => true],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function listing(array $overrides = []): array
    {
        return array_merge([
            'id' => 4731,
            'title' => 'Apartment for sale in Shelly Beach, Margate, KwaZulu Natal',
            'description' => 'Lock-up-and-go apartment a short walk from the beach.',
            'property_type' => 'Apartment',
            'listing_type' => 'sale',
            'status' => 'for_sale',
            'beds' => 2,
            'baths' => 1,
            'size_m2' => 85,
            'suburb' => 'Shelly Beach',
            'town' => 'Margate',
            'province' => 'KwaZulu Natal',
            'price' => 1250000,
            'listed_at' => '2026-09-01T08:00:00+02:00',
            'images' => ['https://cdn.example.com/4731/1.jpg', 'https://cdn.example.com/4731/2.jpg'],
        ], $overrides);
    }

    /**
     * Fake CoreX with one listing, one agent and the agency profile.
     *
     * @param  array<string, mixed>  $listing
     */
    protected function fakeCorex(array $listing = []): void
    {
        $listing = $listing ?: $this->listing();

        Http::fake([
            '*/agency*' => Http::response(['data' => $this->agency()]),
            '*/listings/'.$listing['id'].'*' => Http::response(['data' => $listing]),
            '*/listings*' => Http::response(['data' => [$listing], 'meta' => ['last_page' => 1]]),
            '*/agents/23*' => Http::response(['data' => [
                'id' => 23,
                'name' => 'Elize Reichel',
                'designation' => 'Principal Property Practitioner',
                'email' => 'elize@hfc.test',
                'cell' => '082 000 0000',
                'photo_url' => 'https://cdn.example.com/elize.jpg',
            ]]),
            '*/agents*' => Http::response(['data' => [['id' => 23, 'name' => 'Elize Reichel']]]),
            '*/branches/12*' => Http::response(['data' => [
                'id' => 12,
                'trading_name' => 'HFC Shelly Beach',
                'address' => '3 Marine Drive, Shelly Beach, 4265',
                'phone' => '039 315 1111',
                'agents' => [],
            ]]),
            '*/branches*' => Http::response(['data' => [['id' => 12, 'trading_name' => 'HFC Shelly Beach']], 'meta' => ['last_page' => 1]]),
            '*' => Http::response(['data' => []]),
        ]);
    }

    /**
     * The decoded JSON-LD @graph emitted by the server.
     *
     * @return list<array<string, mixed>>
     */
    protected function jsonLd(TestResponse $response): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);

        $graph = [];

        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $graph = [...$graph, ...($decoded['@graph'] ?? [$decoded])];
        }

        return $graph;
    }

    /**
     * @param  list<array<string, mixed>>  $graph
     * @return array<string, mixed>|null
     */
    protected function node(array $graph, string $type): ?array
    {
        foreach ($graph as $node) {
            if (($node['@type'] ?? null) === $type) {
                return $node;
            }
        }

        return null;
    }

    // 1. The client <Head> title equals the server SEO title.

    public function test_managed_page_title_is_shared_as_the_inertia_seo_title(): void
    {
        $this->fakeCorex();
        Page::query()->create([
            'key' => 'for-sale',
            'name' => 'For Sale',
            'slug' => 'for-sale',
            'meta_title' => 'Properties For Sale South Coast KZN | Home Finders Coastal',
        ]);

        $this->get('/for-sale')
            ->assertOk()
            ->assertSee('<title inertia>Properties For Sale South Coast KZN | Home Finders Coastal</title>', false)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('seo.title', 'Properties For Sale South Coast KZN | Home Finders Coastal'));
    }

    public function test_property_page_shares_its_dynamic_seo_title(): void
    {
        $this->fakeCorex();

        $response = $this->get(route('property.show', 'apartment-for-sale-in-shelly-beach-margate-kwazulu-natal-4731'));

        $title = '2 Bed Apartment for Sale in Shelly Beach | R 1 250 000';
        $response->assertOk()
            ->assertSee('<title inertia>'.$title.'</title>', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('seo.title', $title));
    }

    // 2. Search Console verification.

    public function test_an_html_file_verification_token_is_not_emitted_as_a_meta_tag(): void
    {
        $this->fakeCorex();
        SiteSetting::current()->update(['google_search_console_verification' => 'google-site-verification: google201d508dac7078ff.html']);

        $this->get('/bond-calculator')->assertOk()->assertDontSee('name="google-site-verification"', false);
    }

    public function test_a_bare_verification_code_is_emitted_as_a_meta_tag(): void
    {
        $this->fakeCorex();
        SiteSetting::current()->update(['google_search_console_verification' => 'AbC123_xyz-9']);

        $this->get('/bond-calculator')->assertSee('<meta name="google-site-verification" content="AbC123_xyz-9">', false);
    }

    public function test_the_verification_file_is_served_from_public(): void
    {
        $this->assertSame(
            'google-site-verification: google201d508dac7078ff.html',
            file_get_contents(public_path('google201d508dac7078ff.html')),
        );
    }

    public function test_marketing_settings_reject_a_file_token_for_the_verification_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.marketing.update'), ['google_search_console_verification' => 'google-site-verification: google201d508dac7078ff.html'])
            ->assertSessionHasErrors('google_search_console_verification');

        $this->actingAs($user)
            ->put(route('admin.marketing.update'), ['google_search_console_verification' => 'AbC123_xyz-9'])
            ->assertSessionHasNoErrors();
    }

    // 3. robots.txt is served by the app, not a static file.

    public function test_robots_txt_points_at_the_sitemap_and_disallows_admin(): void
    {
        $this->assertFileDoesNotExist(public_path('robots.txt'));

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.url('sitemap.xml'), false)
            ->assertSee('Disallow: /admin', false);
    }

    // 4. JSON-LD.

    public function test_every_public_page_has_the_agency_organization_node(): void
    {
        $this->fakeCorex();

        $organization = $this->node($this->jsonLd($this->get('/bond-calculator')), 'RealEstateAgent');

        $this->assertNotNull($organization);
        $this->assertSame('HFC Realty', $organization['name']);
        $this->assertSame(url('/'), $organization['url']);
        $this->assertSame('https://corex.test/logo.png', $organization['logo']);
        $this->assertSame('039 315 0000', $organization['telephone']);
        $this->assertSame('1 Marine Drive, Margate', $organization['address']['streetAddress']);
        $this->assertSame('KwaZulu-Natal South Coast', $organization['areaServed']['name']);
        $this->assertSame(['https://facebook.com/hfcoastal'], $organization['sameAs']);
    }

    public function test_the_admin_panel_has_no_generated_json_ld(): void
    {
        $this->fakeCorex();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.marketing.edit'))
            ->assertDontSee('application/ld+json', false);
    }

    public function test_property_page_has_a_real_estate_listing_and_breadcrumbs(): void
    {
        $this->fakeCorex();
        $url = route('property.show', 'apartment-for-sale-in-shelly-beach-margate-kwazulu-natal-4731');

        $graph = $this->jsonLd($this->get($url));
        $listing = $this->node($graph, 'RealEstateListing');
        $breadcrumbs = $this->node($graph, 'BreadcrumbList');

        $this->assertNotNull($listing);
        $this->assertSame($url, $listing['url']);
        $this->assertSame('Lock-up-and-go apartment a short walk from the beach.', $listing['description']);
        $this->assertCount(2, $listing['image']);
        $this->assertEquals(1250000, $listing['offers']['price']);
        $this->assertSame('ZAR', $listing['offers']['priceCurrency']);
        $this->assertSame('2026-09-01T08:00:00+02:00', $listing['datePosted']);
        $this->assertSame('Apartment', $listing['mainEntity']['@type']);
        $this->assertSame(2, $listing['mainEntity']['numberOfBedrooms']);
        $this->assertSame('Shelly Beach', $listing['mainEntity']['address']['addressLocality']);
        $this->assertSame(85, $listing['mainEntity']['floorSize']['value']);

        $this->assertSame(['Home', 'For Sale', 'Apartment for sale in Shelly Beach, Margate, KwaZulu Natal'], array_column($breadcrumbs['itemListElement'], 'name'));
        $this->assertSame($url, $breadcrumbs['itemListElement'][2]['item']);
    }

    public function test_a_script_tag_in_a_description_cannot_break_out_of_the_json_ld(): void
    {
        $this->fakeCorex($this->listing(['description' => 'Nice view</script><script>alert(1)</script>']));

        $response = $this->get(route('property.show', 'apartment-for-sale-in-shelly-beach-margate-kwazulu-natal-4731'));

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $json) {
            $this->assertStringNotContainsString('<', $json);
            $this->assertStringNotContainsString('>', $json);
            json_decode($json, flags: JSON_THROW_ON_ERROR);
        }

        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_agent_page_has_a_person_and_breadcrumbs(): void
    {
        $this->fakeCorex();
        $url = route('agents.show', 'elize-reichel');

        $graph = $this->jsonLd($this->get($url));
        $person = $this->node($graph, 'Person');

        $this->assertSame('Elize Reichel', $person['name']);
        $this->assertSame('Principal Property Practitioner', $person['jobTitle']);
        $this->assertSame('https://cdn.example.com/elize.jpg', $person['image']);
        $this->assertSame('082 000 0000', $person['telephone']);
        $this->assertSame(url('/').'#organization', $person['worksFor']['@id']);
        $this->assertSame(['Home', 'Agents', 'Elize Reichel'], array_column($this->node($graph, 'BreadcrumbList')['itemListElement'], 'name'));
    }

    public function test_article_page_has_an_article_and_breadcrumbs(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->agency()]),
            '*/articles/5*' => Http::response(['data' => []]),
            '*/articles*' => Http::response(['data' => [[
                'id' => 5,
                'agent_id' => 23,
                'title' => 'Buying on the South Coast',
                'slug' => 'buying-on-the-south-coast',
                'excerpt' => 'What to know before you buy.',
                'cover_image_url' => 'https://cdn.example.com/cover.jpg',
                'date' => '2026-08-15',
            ]], 'meta' => ['last_page' => 1]]),
            '*/agents/23*' => Http::response(['data' => ['id' => 23, 'name' => 'Elize Reichel']]),
            '*' => Http::response(['data' => []]),
        ]);

        $graph = $this->jsonLd($this->get(route('articles.show', 'buying-on-the-south-coast')));
        $article = $this->node($graph, 'Article');

        $this->assertSame('Buying on the South Coast', $article['headline']);
        $this->assertSame('https://cdn.example.com/cover.jpg', $article['image']);
        $this->assertStringStartsWith('2026-08-15', $article['datePublished']);
        $this->assertSame('Elize Reichel', $article['author']['name']);
        $this->assertNotNull($this->node($graph, 'BreadcrumbList'));
    }

    public function test_branch_page_has_breadcrumbs(): void
    {
        $this->fakeCorex();

        $breadcrumbs = $this->node($this->jsonLd($this->get(route('branches.show', 12))), 'BreadcrumbList');

        $this->assertSame(['Home', 'Our Branches', 'HFC Shelly Beach'], array_column($breadcrumbs['itemListElement'], 'name'));
    }

    public function test_admin_entered_json_ld_is_still_printed(): void
    {
        $this->fakeCorex();
        Page::query()->create([
            'key' => 'contact',
            'name' => 'Contact',
            'slug' => 'contact',
            'json_ld' => '{"@context":"https://schema.org","@type":"FAQPage"}',
        ]);

        $graph = $this->jsonLd($this->get('/contact'));

        $this->assertNotNull($this->node($graph, 'FAQPage'));
        $this->assertNotNull($this->node($graph, 'RealEstateAgent'));
    }

    // 6. Pages without a CMS row still get real meta.

    public function test_static_pages_have_their_own_title_and_description(): void
    {
        $this->fakeCorex();

        $this->get('/bond-calculator')
            ->assertSee('<title inertia>Bond Repayment Calculator | Home Finders Coastal</title>', false)
            ->assertSee('<meta name="description" content="Work out your monthly home loan repayments', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('seo.title', 'Bond Repayment Calculator | Home Finders Coastal'));

        $this->get('/privacy-policy')
            ->assertSee('<title inertia>Privacy Policy | Home Finders Coastal</title>', false)
            ->assertSee('Protection of Personal Information Act', false);
    }

    public function test_branch_pages_have_their_own_title_and_description(): void
    {
        $this->fakeCorex();

        $this->get('/branches')
            ->assertSee('<title inertia>Our Branches | Home Finders Coastal</title>', false)
            ->assertSee('<meta name="description" content="Find your nearest', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('seo.title', 'Our Branches | Home Finders Coastal'));

        $this->get(route('branches.show', 12))
            ->assertSee('<title inertia>HFC Shelly Beach Estate Agents | Home Finders Coastal</title>', false)
            ->assertSee('<meta name="description" content="Visit our HFC Shelly Beach office, Home Finders Coastal’s local branch', false)
            ->assertSee('Call 039 315 1111.', false)
            ->assertInertia(fn (AssertableInertia $page) => $page->where('seo.title', 'HFC Shelly Beach Estate Agents | Home Finders Coastal'));
    }

    // 7. Paginated canonicals.

    public function test_page_two_of_a_listing_index_canonicalises_to_itself_without_filters(): void
    {
        $this->fakeCorex();

        $this->get('/for-sale?page=2&suburb=Margate')
            ->assertSee('<link rel="canonical" href="'.url('for-sale').'?page=2">', false)
            ->assertSee('<meta property="og:url" content="'.url('for-sale').'?page=2">', false);
    }

    public function test_page_one_and_filters_canonicalise_to_the_base_path(): void
    {
        $this->fakeCorex();

        $this->get('/for-sale?page=1')->assertSee('<link rel="canonical" href="'.url('for-sale').'">', false);
        $this->get('/to-rent?suburb=Margate')->assertSee('<link rel="canonical" href="'.url('to-rent').'">', false);
        $this->get('/bond-calculator?page=3')->assertSee('<link rel="canonical" href="'.url('bond-calculator').'">', false);
    }

    // 8. /index.php duplicates.

    public function test_index_php_urls_redirect_permanently_to_the_clean_path(): void
    {
        $this->get('/index.php')->assertStatus(301)->assertHeader('Location', url('/').'/');
        $this->get('/index.php/for-sale?page=2')->assertStatus(301)->assertHeader('Location', url('/for-sale').'?page=2');
        $this->get('/index.phpx')->assertStatus(404);
    }

    // 9. Social meta.

    public function test_og_site_name_falls_back_to_the_app_name(): void
    {
        $this->fakeCorex();

        $this->get('/bond-calculator')->assertSee('<meta property="og:site_name" content="'.config('app.name').'">', false);
    }

    public function test_listing_share_image_has_alt_text(): void
    {
        $this->fakeCorex();

        $this->get(route('property.show', 'apartment-for-sale-in-shelly-beach-margate-kwazulu-natal-4731'))
            ->assertSee('<meta property="og:image:alt" content="Apartment for sale in Shelly Beach, Margate, KwaZulu Natal">', false)
            ->assertSee('<meta name="twitter:image:alt" content="Apartment for sale in Shelly Beach, Margate, KwaZulu Natal">', false);
    }
}
