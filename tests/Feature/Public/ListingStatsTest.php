<?php

namespace Tests\Feature\Public;

use App\Models\ListingStat;
use App\Services\Stats\ListingStatEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The website side of the CoreX stats loop: what gets counted, and what is
 * deliberately not counted.
 */
class ListingStatsTest extends TestCase
{
    use RefreshDatabase;

    /** A believable desktop browser, so the crawler filter lets the hit through. */
    protected const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    protected function fakeListing(): void
    {
        Http::fake([
            '*/listings/*' => Http::response([
                'data' => ['id' => 42, 'reference' => 'HFC42', 'title' => 'Sea View Villa', 'listing_type' => 'sale', 'status' => 'for_sale'],
            ]),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 42, 'reference' => 'HFC42', 'title' => 'Sea View Villa', 'listing_type' => 'sale', 'status' => 'for_sale'],
                    ['id' => 43, 'reference' => 'HFC43', 'title' => 'Dune Cottage', 'listing_type' => 'sale', 'status' => 'for_sale'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    /**
     * The stored count for one listing/event, or 0 when nothing was recorded.
     */
    protected function counted(string $listingId, ListingStatEvent $event): int
    {
        return (int) ListingStat::query()
            ->where('listing_id', $listingId)
            ->where('event', $event->value)
            ->sum('count');
    }

    public function test_a_property_page_view_is_counted(): void
    {
        $this->fakeListing();

        $this->withHeader('User-Agent', self::BROWSER)
            ->get(route('property.show', 'sea-view-villa-42'))
            ->assertOk();

        $this->assertSame(1, $this->counted('42', ListingStatEvent::DetailView));
        $this->assertSame(1, $this->counted('42', ListingStatEvent::UniqueDetailView));
        $this->assertSame('HFC42', ListingStat::query()->first()->reference);
    }

    public function test_a_repeat_visit_counts_another_view_but_not_another_unique_view(): void
    {
        $this->fakeListing();

        foreach (range(1, 3) as $ignored) {
            $this->withHeader('User-Agent', self::BROWSER)
                ->get(route('property.show', 'sea-view-villa-42'))
                ->assertOk();
        }

        $this->assertSame(3, $this->counted('42', ListingStatEvent::DetailView));
        $this->assertSame(1, $this->counted('42', ListingStatEvent::UniqueDetailView));
    }

    public function test_a_crawler_view_is_not_counted(): void
    {
        $this->fakeListing();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get(route('property.show', 'sea-view-villa-42'))
            ->assertOk();

        $this->assertSame(0, ListingStat::query()->count());
    }

    public function test_a_non_canonical_url_counts_the_view_only_once(): void
    {
        $this->fakeListing();

        // A bare id redirects to the title slug; only the canonical render counts.
        $this->withHeader('User-Agent', self::BROWSER)
            ->get(route('property.show', '42'))
            ->assertRedirect(route('property.show', 'sea-view-villa-42'));

        $this->assertSame(0, $this->counted('42', ListingStatEvent::DetailView));
    }

    public function test_a_results_page_counts_one_impression_per_listing(): void
    {
        $this->fakeListing();

        $this->withHeader('User-Agent', self::BROWSER)
            ->get(route('for-sale'))
            ->assertOk();

        $this->assertSame(1, $this->counted('42', ListingStatEvent::Impression));
        $this->assertSame(1, $this->counted('43', ListingStatEvent::Impression));

        // A second page view bumps the same rows rather than creating new ones.
        $this->withHeader('User-Agent', self::BROWSER)
            ->get(route('for-sale'))
            ->assertOk();

        $this->assertSame(2, $this->counted('42', ListingStatEvent::Impression));
        $this->assertSame(2, ListingStat::query()->where('event', ListingStatEvent::Impression->value)->count());
    }

    public function test_an_enquiry_is_counted_against_the_listing(): void
    {
        $this->fakeListing();
        Mail::fake();

        $this->withHeader('User-Agent', self::BROWSER)
            ->post(route('property.enquire', 'sea-view-villa-42'), [
                'name' => 'Thandi Mokoena',
                'email' => 'thandi@example.com',
                'phone' => '082 000 0000',
                'message' => 'Is this still available?',
                'company' => '',
            ])
            ->assertRedirect();

        $this->assertSame(1, $this->counted('42', ListingStatEvent::Enquiry));
    }

    public function test_the_beacon_endpoint_records_a_browser_reportable_event(): void
    {
        $this->fakeListing();

        $this->withHeader('User-Agent', self::BROWSER)
            ->postJson(route('listing-events.store'), ['listing_id' => '42', 'event' => 'phone_click'])
            ->assertNoContent();

        $this->assertSame(1, $this->counted('42', ListingStatEvent::PhoneClick));
        $this->assertSame('HFC42', ListingStat::query()->first()->reference);
    }

    public function test_the_beacon_endpoint_rejects_server_side_events(): void
    {
        $this->fakeListing();

        foreach (['detail_view', 'unique_detail_view', 'impression', 'enquiry'] as $event) {
            $this->withHeader('User-Agent', self::BROWSER)
                ->postJson(route('listing-events.store'), ['listing_id' => '42', 'event' => $event])
                ->assertStatus(422)
                ->assertJsonValidationErrors('event');
        }

        $this->assertSame(0, ListingStat::query()->count());
    }

    public function test_the_beacon_endpoint_ignores_a_listing_corex_does_not_know(): void
    {
        Http::fake(['*/listings/*' => Http::response(['data' => []], 404)]);

        $this->withHeader('User-Agent', self::BROWSER)
            ->postJson(route('listing-events.store'), ['listing_id' => '999999', 'event' => 'share_click'])
            ->assertNoContent();

        $this->assertSame(0, ListingStat::query()->count());
    }

    public function test_the_beacon_endpoint_ignores_crawlers(): void
    {
        $this->fakeListing();

        $this->withHeader('User-Agent', 'python-requests/2.31.0')
            ->postJson(route('listing-events.store'), ['listing_id' => '42', 'event' => 'gallery_open'])
            ->assertNoContent();

        $this->assertSame(0, ListingStat::query()->count());
    }
}
