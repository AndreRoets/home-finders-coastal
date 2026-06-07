<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Sole-mandate listings must surface on HFC Exclusive (with the "exclusive"
 * badge) and sold listings on the Sold page (with the "sold" badge), regardless
 * of the exact casing/field the CoreX feed uses to express "sole".
 */
class ExclusiveSoldMappingTest extends TestCase
{
    protected function fakeListings(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    // Various ways the feed may flag a sole mandate — all should be Exclusives.
                    ['id' => 1, 'title' => 'Sole lowercase', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate_type' => 'sole'],
                    ['id' => 2, 'title' => 'Sole capitalised', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate_type' => 'Sole'],
                    ['id' => 3, 'title' => 'Sole via mandate field', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate' => 'Sole Mandate'],
                    ['id' => 4, 'title' => 'Sole via boolean', 'listing_type' => 'sale', 'status' => 'for_sale', 'sole_mandate' => true],
                    // Open mandate — NOT exclusive.
                    ['id' => 5, 'title' => 'Open mandate', 'listing_type' => 'sale', 'status' => 'for_sale', 'mandate_type' => 'open'],
                    // Sold (capitalised) — Sold page only, never Exclusive or For Sale.
                    ['id' => 6, 'title' => 'Sold home', 'listing_type' => 'sale', 'status' => 'Sold', 'mandate_type' => 'sole'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    public function test_sole_mandate_variants_all_appear_in_exclusives(): void
    {
        $this->fakeListings();

        $this->get(route('hfc-exclusive'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('hfc-exclusive')
                // ids 1-4 are sole and available; 5 is open, 6 is sold → excluded.
                ->has('listings', 4)
                ->where('listings.0.status', 'exclusive')
            );
    }

    public function test_sold_listing_appears_on_sold_page_with_sold_badge(): void
    {
        $this->fakeListings();

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sold')
                ->has('listings', 1)
                ->where('listings.0.title', 'Sold home')
                ->where('listings.0.status', 'sold') // drives the "Sold" badge on the card
            );
    }

    public function test_sold_listing_is_excluded_from_for_sale_and_exclusives(): void
    {
        $this->fakeListings();

        // Sold home (id 6) is a sole mandate but it is sold, so it must NOT appear
        // in For Sale or Exclusives — only on the Sold page.
        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('listings', fn ($listings) => collect($listings)->doesntContain('title', 'Sold home'))
            );
    }
}
