<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ListingSearchTest extends TestCase
{
    /**
     * Fake CoreX with a known mix of listings for search assertions.
     */
    protected function fakeListings(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'Clifftop Villa', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Camps Bay', 'property_type' => 'Villa', 'beds' => 4, 'baths' => 3, 'price' => 10_000_000],
                    ['id' => 2, 'title' => 'City Apartment', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Sea Point', 'property_type' => 'Apartment', 'beds' => 2, 'baths' => 1, 'price' => 3_000_000],
                    ['id' => 3, 'title' => 'Beach Flat', 'listing_type' => 'rental', 'status' => 'to_let', 'suburb' => 'Camps Bay', 'property_type' => 'Apartment', 'beds' => 1, 'baths' => 1, 'rental' => ['rental_amount' => 15000, 'rental_price_type' => 'per_month']],
                    ['id' => 4, 'title' => 'Sold House', 'listing_type' => 'sale', 'status' => 'sold', 'suburb' => 'Llandudno', 'property_type' => 'House', 'beds' => 5, 'baths' => 4, 'price' => 20_000_000],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    public function test_for_sale_facets_only_reflect_active_sale_listings(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters.suburbs', ['Camps Bay', 'Sea Point']) // sold + rental suburbs excluded, sorted
                ->where('filters.suggestions', ['Camps Bay', 'Sea Point']) // autocomplete only offers live places
                ->where('filters.propertyTypes', ['Apartment', 'Villa'])
                ->where('filters.maxBeds', 4)
                ->where('filters.maxBaths', 3)
                ->where('filters.price.sale.min', 3_000_000)
                ->where('filters.price.sale.max', 10_000_000)
            );
    }

    public function test_for_sale_filters_by_suburb(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['suburb' => 'Sea Point']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.title', 'City Apartment')
            );
    }

    public function test_for_sale_filters_by_multiple_suburbs(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['suburb' => ['Camps Bay', 'Sea Point']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 2) // both active sale suburbs, rental + sold excluded
                ->where('search.suburbs', ['Camps Bay', 'Sea Point'])
            );
    }

    public function test_for_sale_filters_by_multiple_property_types(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['type' => ['Villa', 'House']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1) // only the active Villa; House is sold
                ->where('listings.data.0.title', 'Clifftop Villa')
                ->where('search.types', ['Villa', 'House'])
            );
    }

    public function test_for_sale_filters_by_min_beds_and_price(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['beds' => 3, 'min_price' => 5_000_000]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.title', 'Clifftop Villa')
            );
    }

    public function test_for_sale_filters_by_keyword(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['q' => 'apartment']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.title', 'City Apartment')
            );
    }

    public function test_to_rent_price_facets_use_rental_amounts(): void
    {
        $this->fakeListings();

        $this->get(route('to-rent'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1)
                ->where('filters.price.rent.min', 15000)
                ->where('filters.price.rent.max', 15000)
            );
    }

    public function test_keyword_suggestions_pull_places_from_live_listings_only(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'A', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Shelly Beach', 'town' => 'Margate', 'city' => 'Margate', 'property_type' => 'House', 'price' => 1_000_000],
                    // Same town as above (and a differently-cased duplicate) collapse to one entry.
                    ['id' => 2, 'title' => 'B', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'shelly beach', 'town' => 'Margate', 'city' => 'Port Edward', 'property_type' => 'House', 'price' => 2_000_000],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                // Deduped (case-insensitive), naturally sorted, and never anything
                // that isn't on a live listing.
                ->where('filters.suggestions', ['Margate', 'Port Edward', 'Shelly Beach'])
            );
    }

    public function test_for_sale_paginates_at_twenty_one_per_page(): void
    {
        $listings = [];

        for ($i = 1; $i <= 25; $i++) {
            $listings[] = [
                'id' => $i,
                'title' => 'Sale '.$i,
                'listing_type' => 'sale',
                'status' => 'for_sale',
                'suburb' => 'Margate',
                'property_type' => 'House',
                'price' => 1_000_000,
            ];
        }

        Http::fake([
            '*/listings*' => Http::response(['data' => $listings, 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 21)
                ->where('listings.total', 25)
                ->where('listings.current_page', 1)
                ->where('listings.last_page', 2)
            );

        $this->get(route('for-sale', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 4)
                ->where('listings.current_page', 2)
            );
    }

    public function test_home_exposes_search_facets(): void
    {
        $this->fakeListings();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home')
                ->has('filters.suburbs')
                ->where('filters.maxBeds', 4)
            );
    }
}
