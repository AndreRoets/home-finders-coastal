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
                ->has('listings', 1)
                ->where('listings.0.title', 'City Apartment')
            );
    }

    public function test_for_sale_filters_by_min_beds_and_price(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['beds' => 3, 'min_price' => 5_000_000]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.title', 'Clifftop Villa')
            );
    }

    public function test_for_sale_filters_by_keyword(): void
    {
        $this->fakeListings();

        $this->get(route('for-sale', ['q' => 'apartment']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('listings.0.title', 'City Apartment')
            );
    }

    public function test_to_rent_price_facets_use_rental_amounts(): void
    {
        $this->fakeListings();

        $this->get(route('to-rent'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings', 1)
                ->where('filters.price.rent.min', 15000)
                ->where('filters.price.rent.max', 15000)
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
