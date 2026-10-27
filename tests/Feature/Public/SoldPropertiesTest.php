<?php

namespace Tests\Feature\Public;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Since 27 Oct 2026 CoreX serves sold stock from GET /sold (not /listings),
 * with sale_price / sold_at and no street address. The Sold page, agent
 * profiles, cache warming and the listing.removed webhook all read it.
 */
class SoldPropertiesTest extends TestCase
{
    /**
     * A raw CoreX /sold item.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function soldItem(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'property_id' => 100,
            'cycle' => 1,
            'title' => 'Sold Villa',
            'property_type' => 'House',
            'listing_type' => 'sale',
            'address' => 'Uvongo, Margate',
            'suburb' => 'Uvongo',
            'city' => 'Margate',
            'beds' => 3,
            'baths' => 2,
            'size_m2' => 180,
            'asking_price' => 2_500_000,
            'sale_price' => 2_350_000,
            'sold_at' => '2026-10-20',
            'days_on_market' => 45,
            'images' => ['https://images.test/cover.jpg', 'https://images.test/2.jpg'],
            'agents' => [['id' => 7, 'name' => 'Thandi Mbeki', 'is_primary' => true]],
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function fakeSold(array $items): void
    {
        Http::fake([
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/sold*' => Http::response(['data' => $items, 'meta' => ['last_page' => 1]]),
        ]);
    }

    public function test_sold_page_shows_sold_items_newest_sale_first_with_sale_price_and_date(): void
    {
        $this->fakeSold([
            $this->soldItem(['id' => 1, 'title' => 'Older Sale', 'sold_at' => '2026-09-01']),
            $this->soldItem(['id' => 2, 'title' => 'Newest Sale', 'sold_at' => '2026-10-25']),
            $this->soldItem(['id' => 3, 'title' => 'Middle Sale', 'sold_at' => '2026-10-01']),
        ]);

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('sold')
                ->where('listings.total', 3)
                ->where('listings.data.0.title', 'Newest Sale')
                ->where('listings.data.1.title', 'Middle Sale')
                ->where('listings.data.2.title', 'Older Sale')
                ->where('listings.data.0.status', 'sold')
                ->where('listings.data.0.price', 'R 2 350 000') // sale_price, not asking_price
                ->where('listings.data.0.soldAt', '2026-10-25')
                ->where('listings.data.0.soldOn', '25 Oct 2026')
                ->where('listings.data.0.daysOnMarket', 45)
                ->where('listings.data.0.location', 'Uvongo, Margate')
                ->where('listings.data.0.image', 'https://images.test/cover.jpg')
                ->where('listings.data.0.slug', null) // no property page for sold stock
                ->where('listings.data.0.agent.name', 'Thandi Mbeki')
            );

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/sold'));
    }

    public function test_sold_page_never_falls_back_to_the_asking_price(): void
    {
        $this->fakeSold([$this->soldItem(['sale_price' => null])]);

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('listings.data.0.price', 'Price undisclosed')
            );
    }

    public function test_sold_page_ignores_sold_rows_on_the_listings_endpoint(): void
    {
        Http::fake([
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/sold*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/listings*' => Http::response([
                'data' => [['id' => 3, 'title' => 'Stale Sold Listing', 'listing_type' => 'sale', 'status' => 'sold']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('listings.data', 0));
    }

    public function test_sold_page_follows_every_page_of_the_sold_endpoint(): void
    {
        Http::fake([
            '*/agents*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/sold*' => function (Request $request) {
                $page = (int) ($request->data()['page'] ?? 1);
                $items = [];

                for ($i = 1; $i <= 50; $i++) {
                    $id = ($page - 1) * 50 + $i;
                    $items[] = $this->soldItem(['id' => $id, 'title' => 'Sale '.$id, 'sold_at' => date('Y-m-d', strtotime('2026-10-26 -'.$id.' days'))]);
                }

                return Http::response(['data' => $items, 'meta' => ['last_page' => 10]]);
            },
        ]);

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('listings.total', 500)
                ->has('listings.data', 21)
                ->where('listings.data.0.title', 'Sale 1')
            );
    }

    public function test_sold_page_hides_items_whose_only_agent_is_turned_off(): void
    {
        Http::fake([
            '*/agents*' => Http::response(['data' => [['id' => 7, 'name' => 'Thandi Mbeki']], 'meta' => ['last_page' => 1]]),
            '*/sold*' => Http::response([
                'data' => [
                    $this->soldItem(['id' => 1, 'title' => 'Visible Sold', 'sold_at' => '2026-10-03']),
                    $this->soldItem(['id' => 2, 'title' => 'Hidden Agent Sold', 'sold_at' => '2026-10-02', 'agents' => [['id' => 29, 'name' => 'Maggie Venter']]]),
                    $this->soldItem(['id' => 3, 'title' => 'No Agent Sold', 'sold_at' => '2026-10-01', 'agents' => []]),
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('sold'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 2)
                ->where('listings.data.0.title', 'Visible Sold')
                ->where('listings.data.1.title', 'No Agent Sold')
            );
    }

    public function test_for_sale_excludes_sold_and_prospecting_listings(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'On Market', 'listing_type' => 'sale', 'status' => 'for_sale'],
                    ['id' => 2, 'title' => 'Sold One', 'listing_type' => 'sale', 'status' => 'sold'],
                    ['id' => 3, 'title' => 'Prospect One', 'listing_type' => 'sale', 'status' => 'prospecting'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.title', 'On Market')
            );
    }

    public function test_agent_profile_shows_the_agents_sold_items_from_the_sold_endpoint(): void
    {
        Http::fake([
            '*/agents/7' => Http::response(['data' => ['id' => 7, 'name' => 'Thandi Mbeki']]),
            '*/listings*' => Http::response([
                'data' => [['id' => 1, 'title' => 'Live Listing', 'listing_type' => 'sale', 'status' => 'for_sale']],
                'meta' => ['last_page' => 1],
            ]),
            '*/sold*' => Http::response([
                'data' => [
                    $this->soldItem(['id' => 1, 'title' => 'Thandi Older Sale', 'sold_at' => '2026-08-01']),
                    $this->soldItem(['id' => 2, 'title' => 'Someone Else Sale', 'agents' => [['id' => 29, 'name' => 'Maggie Venter']]]),
                    $this->soldItem(['id' => 3, 'title' => 'Thandi Co-listed Sale', 'sold_at' => '2026-10-01', 'agents' => [
                        ['id' => 29, 'name' => 'Maggie Venter', 'is_primary' => true],
                        ['id' => 7, 'name' => 'Thandi Mbeki', 'is_primary' => false],
                    ]]),
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->has('listings', 3)
                ->where('listings.0.title', 'Live Listing')
                ->where('listings.1.title', 'Thandi Co-listed Sale')
                ->where('listings.1.id', 'sold-3') // never collides with live listing id 3
                ->where('listings.1.status', 'sold')
                ->where('listings.2.title', 'Thandi Older Sale')
            );
    }

    public function test_warm_cache_stores_the_sold_collection(): void
    {
        config(['services.corex.cache_ttl' => 300]);

        Http::fake(['*' => Http::response(['data' => [['id' => 9]], 'meta' => ['last_page' => 1]])]);

        $this->artisan('corex:warm-cache')->assertSuccessful();

        $this->assertSame([['id' => 9]], Cache::get('corex:sold:'.md5(serialize([])).':50'));
    }
}
