<?php

namespace Tests\Feature\Public;

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
