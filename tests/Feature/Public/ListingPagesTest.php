<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ListingPagesTest extends TestCase
{
    /**
     * Fake the listings endpoint with a mixed set of listings.
     */
    protected function fakeListings(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'For Sale Home', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate_type' => null, 'price_display' => 'R 1,000,000', 'suburb' => 'Camps Bay'],
                    ['id' => 2, 'title' => 'Rental Flat', 'listing_type' => 'rental', 'status' => 'to_let', 'rental' => ['rental_amount' => 9000, 'rental_price_type' => 'per_month']],
                    ['id' => 3, 'title' => 'Sold House', 'listing_type' => 'sale', 'status' => 'sold'],
                    ['id' => 4, 'title' => 'Exclusive Estate', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate_type' => 'sole'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    public function test_for_sale_page_shows_only_active_sales(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('for-sale')
                ->has('listings', 2) // sale + exclusive (both active sales)
                ->where('listings.0.status', 'for-sale')
            );
    }

    public function test_to_rent_page_shows_only_rentals(): void
    {
        $this->fakeListings();

        $this->get(route('to-rent'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.status', 'to-rent')
                ->where('listings.0.price', 'R 9 000 / month')
            );
    }

    public function test_sold_page_shows_only_sold(): void
    {
        $this->fakeListings();

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.status', 'sold')
            );
    }

    public function test_exclusive_page_shows_sole_mandates_with_exclusive_badge(): void
    {
        $this->fakeListings();

        $this->get(route('hfc-exclusive'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.title', 'Exclusive Estate')
                ->where('listings.0.status', 'exclusive')
            );
    }

    public function test_pages_render_even_when_corex_is_unavailable(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'This agency website is not currently live.'], 403),
        ]);

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('listings', 0));
    }

    public function test_agents_page_renders_mapped_agents(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [[
                    'id' => 12,
                    'name' => 'Thandi Mbeki',
                    'email' => 'thandi@example.test',
                    'cell' => '+27 82 000 0000',
                    'photo_url' => 'https://corex.test/agent.jpg',
                ]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agents')
                ->where('agents.0.name', 'Thandi Mbeki')
                ->where('agents.0.phone', '+27 82 000 0000')
                ->where('agents.0.photo', 'https://corex.test/agent.jpg')
            );
    }

    public function test_agents_page_falls_back_to_listing_agents_when_endpoint_is_empty(): void
    {
        Http::fake([
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'agent' => ['id' => 29, 'name' => 'Maggie Venter', 'cell' => '062 604 4068']],
                    ['id' => 2, 'agent' => ['id' => 29, 'name' => 'Maggie Venter', 'cell' => '062 604 4068']],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('agents', 1) // deduplicated by id
                ->where('agents.0.name', 'Maggie Venter')
            );
    }

    /**
     * The listing payload used by the property-detail tests. Resolved by id (11).
     *
     * @return array<string, mixed>
     */
    protected function fakeSingleListing(): void
    {
        Http::fake([
            '*/listings/11' => Http::response(['data' => [
                'id' => 11,
                'reference' => 'abc-ref',
                'title' => 'Uvongo Apartment',
                'description' => 'Best buy ever.',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'price_display' => 'R 799,000',
                'beds' => 2,
                'baths' => 2,
                'garages' => 2,
                'size_m2' => 75,
                'suburb' => 'Uvongo Beach',
                'city' => 'Margate',
                'agent' => ['id' => 29, 'name' => 'Maggie Venter', 'cell' => '062 604 4068', 'photo_url' => 'https://corex.test/a.jpg'],
            ]]),
        ]);
    }

    public function test_property_detail_page_renders_a_single_listing(): void
    {
        $this->fakeSingleListing();

        $this->get(route('property.show', 'uvongo-apartment-11'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('property-detail')
                ->where('property.title', 'Uvongo Apartment')
                ->where('property.slug', 'uvongo-apartment-11')
                ->where('property.price', 'R 799,000')
                ->where('property.garages', 2)
                ->where('property.agent.name', 'Maggie Venter')
            );
    }

    public function test_property_detail_redirects_legacy_url_to_canonical_slug(): void
    {
        $this->fakeSingleListing();

        // A bare id (and equally an agency reference) 301s to the title slug.
        $this->get(route('property.show', '11'))
            ->assertRedirect(route('property.show', 'uvongo-apartment-11'))
            ->assertStatus(301);
    }

    public function test_property_detail_returns_404_for_unknown_listing(): void
    {
        Http::fake([
            '*/listings/*' => Http::response(['message' => 'Not found.'], 404),
        ]);

        $this->get(route('property.show', 'missing'))->assertNotFound();
    }

    public function test_property_detail_renders_spaces_grouped_features_and_video(): void
    {
        // A listing with the full enriched payload: a pool, parking, grouped
        // security/connectivity features, a YouTube video and a virtual tour.
        Http::fake([
            '*/listings/12' => Http::response(['data' => [
                'id' => 12,
                'title' => 'Clifftop Villa',
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'price_display' => 'R 9,500,000',
                'features' => ['Intercom', 'CCTV', 'Fibre', 'Pool'],
                'features_grouped' => [
                    ['group' => 'security', 'label' => 'Security', 'items' => ['Intercom', 'CCTV']],
                    ['group' => 'connectivity', 'label' => 'Connectivity', 'items' => ['Fibre']],
                    ['group' => 'other', 'label' => 'Other', 'items' => ['Pool']],
                ],
                'spaces' => [
                    ['type' => 'Pool', 'count' => 1, 'features' => ['Heated'], 'description' => 'Sparkling'],
                    ['type' => 'Parking', 'count' => 2, 'features' => []],
                ],
                'video' => [
                    'youtube_id' => 'abc123',
                    'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
                    'matterport_id' => null,
                    'virtual_tour_url' => 'https://tour.example/1',
                ],
            ]]),
        ]);

        $this->get(route('property.show', 'clifftop-villa-12'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('property-detail')
                ->where('property.featuresGrouped.0.label', 'Security')
                ->where('property.featuresGrouped.0.items', ['Intercom', 'CCTV'])
                ->where('property.spaces.0.type', 'Pool')
                ->where('property.spaces.0.count', 1)
                ->where('property.spaces.0.features', ['Heated'])
                ->where('property.spaces.1.type', 'Parking')
                ->where('property.video.youtubeId', 'abc123')
                ->where('property.video.virtualTourUrl', 'https://tour.example/1')
                ->where('property.video.matterportId', null)
            );
    }
}
