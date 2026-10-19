<?php

namespace Tests\Feature\Public;

use App\Models\ListingSeo;
use App\Support\DynamicSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The dynamic, CoreX-backed pages (properties, agents, articles) are not
 * CMS-managed, so their SEO meta is generated server-side from the entity.
 * These cover that generation and the dynamic URLs added to the sitemap.
 */
class DynamicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_detail_page_renders_its_own_seo_meta(): void
    {
        Http::fake([
            '*/listings/55*' => Http::response(['data' => [
                'id' => 55,
                'title' => 'Seaside Villa',
                'description' => 'A sun-drenched four bedroom villa moments from the beach.',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'images' => ['https://cdn.example.com/55/1.jpg'],
            ]]),
            '*' => Http::response(['data' => []]),
        ]);

        $response = $this->get(route('property.show', 'seaside-villa-55'));

        $response->assertOk();
        // Per-listing title and description, not the generic site default.
        $response->assertSee('Seaside Villa', false);
        $response->assertSee('A sun-drenched four bedroom villa moments from the beach.', false);
        // Canonical points at the clean, query-free slug URL.
        $response->assertSee('<link rel="canonical" href="'.route('property.show', 'seaside-villa-55').'">', false);
        $response->assertSee('<meta property="og:title" content="Seaside Villa', false);
    }

    public function test_property_with_no_description_falls_back_to_a_generated_summary(): void
    {
        Http::fake([
            '*/listings/77*' => Http::response(['data' => [
                'id' => 77,
                'title' => 'Hillside Plot',
                'beds' => 3,
                'property_type' => 'House',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'suburb' => 'Margate',
            ]]),
            '*' => Http::response(['data' => []]),
        ]);

        $this->get(route('property.show', 'hillside-plot-77'))
            ->assertOk()
            ->assertSee('3 bedroom House for sale', false);
    }

    public function test_listing_title_is_built_from_structured_fields_without_the_province(): void
    {
        $title = DynamicSeo::listingTitle([
            'title' => 'Apartment for sale in Shelly Beach, Margate, KwaZulu Natal',
            'propertyType' => 'Apartment',
            'listingType' => 'sale',
            'beds' => 2,
            'suburb' => 'Shelly Beach',
            'town' => 'Margate',
            'price' => 'R 1 250 000',
            'priceAmount' => 1250000.0,
        ]);

        // The site suffix doesn't fit alongside the price, so it is dropped — never cut mid-word.
        $this->assertSame('2 Bed Apartment for Sale in Shelly Beach | R 1 250 000', $title);
        $this->assertStringNotContainsString('KwaZulu', $title);
        $this->assertStringNotContainsString('…', $title);
    }

    public function test_listing_title_keeps_the_site_suffix_when_it_fits(): void
    {
        $title = DynamicSeo::listingTitle([
            'propertyType' => 'house',
            'listingType' => 'to_let',
            'beds' => 3,
            'suburb' => 'Uvongo',
            'price' => 'Price on application',
            'priceAmount' => null,
        ]);

        $this->assertSame('3 Bed House to Rent in Uvongo | Home Finders Coastal', $title);
    }

    public function test_a_long_suburb_is_never_cut_and_the_title_stays_within_sixty_characters(): void
    {
        $title = DynamicSeo::listingTitle([
            'propertyType' => 'Townhouse',
            'listingType' => 'sale',
            'beds' => 4,
            'suburb' => 'Port Edward Extension Seventeen',
            'price' => 'R 3 500 000',
            'priceAmount' => 3500000.0,
        ]);

        $this->assertSame('4 Bed Townhouse for Sale in Port Edward Extension Seventeen', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
    }

    public function test_a_listing_without_structured_fields_falls_back_to_a_word_boundary_headline(): void
    {
        $title = DynamicSeo::listingTitle([
            'title' => 'Magnificent sea-facing family home with flatlet and sweeping ocean views',
        ]);

        $this->assertLessThanOrEqual(60, mb_strlen($title));
        $this->assertSame('Magnificent sea-facing family home with flatlet and…', $title);
    }

    public function test_agent_title_drops_a_designation_that_does_not_fit(): void
    {
        $long = DynamicSeo::forAgent(['name' => 'Elize Reichel', 'designation' => 'Principal Property Practitioner'], 'https://example.test/agents/elize-reichel');
        $short = DynamicSeo::forAgent(['name' => 'Elize Reichel', 'designation' => 'Rentals'], 'https://example.test/agents/elize-reichel');

        $this->assertSame('Elize Reichel | Home Finders Coastal', $long->meta_title);
        $this->assertSame('Elize Reichel – Rentals | Home Finders Coastal', $short->meta_title);
    }

    public function test_an_admin_listing_override_still_wins_over_the_generated_title(): void
    {
        ListingSeo::query()->create(['listing_id' => '55', 'meta_title' => 'Hand-written listing title']);

        $page = DynamicSeo::forListing(['id' => 55, 'propertyType' => 'House', 'suburb' => 'Margate'], 'https://example.test/property/x-55');

        $this->assertSame('Hand-written listing title', $page->meta_title);
    }

    public function test_sitemap_includes_dynamic_property_and_agent_urls(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 11, 'title' => 'Ocean Cottage', 'listing_type' => 'sale', 'status' => 'for_sale'],
                    ['id' => 12, 'title' => 'Lagoon Loft', 'listing_type' => 'rental', 'status' => 'to_let'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents*' => Http::response(['data' => [
                ['id' => 23, 'name' => 'Elize Reichel'],
            ]]),
            '*' => Http::response(['data' => []]),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('property.show', 'ocean-cottage-11'), false);
        $response->assertSee(route('property.show', 'lagoon-loft-12'), false);
        $response->assertSee(route('agents.show', 'elize-reichel'), false);
    }

    public function test_sitemap_degrades_gracefully_when_corex_is_unavailable(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        // A CoreX outage must not 500 the sitemap — it still serves valid XML.
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<urlset', false);
    }
}
