<?php

namespace Tests\Feature;

use App\Models\ListingStat;
use App\Services\Stats\ListingStatEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The CoreX side of the stats loop: what `corex:push-stats` sends, and how it
 * behaves when CoreX does not accept it.
 */
class CorexPushStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Let CoreX accept the batch. Registered per test rather than in setUp so a
     * test that needs a rejection is not shadowed by an earlier stub.
     */
    protected function fakeCorexAccepts(): void
    {
        Http::fake(['*/listings/stats' => Http::response(['ok' => true])]);
    }

    /**
     * The decoded body of the single stats request that was sent.
     *
     * @return array<string, mixed>
     */
    protected function sentPayload(): array
    {
        $payload = [];

        Http::assertSent(function (Request $request) use (&$payload): bool {
            if (! str_contains($request->url(), '/listings/stats')) {
                return false;
            }

            $payload = $request->data();

            return true;
        });

        return $payload;
    }

    public function test_it_sends_outstanding_deltas_and_marks_them_pushed(): void
    {
        $this->fakeCorexAccepts();

        ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10, 'reference' => 'HFC42']);
        ListingStat::factory()->forListing('42', ListingStatEvent::Enquiry)->create(['count' => 2, 'reference' => 'HFC42']);

        $this->artisan('corex:push-stats')->assertSuccessful();

        $payload = $this->sentPayload();

        $this->assertSame('website', $payload['source']);
        $this->assertNotEmpty($payload['batch_id']);
        $this->assertCount(1, $payload['listings']);

        $listing = $payload['listings'][0];
        $this->assertSame('42', $listing['listing_id']);
        $this->assertSame('HFC42', $listing['reference']);
        $this->assertSame(['detail_view' => 10, 'enquiry' => 2], $listing['delta']);
        $this->assertSame(['detail_view' => 10, 'enquiry' => 2], $listing['totals']);
        $this->assertSame(now()->toDateString(), $listing['days'][0]['date']);
        $this->assertSame(['detail_view' => 10, 'enquiry' => 2], $listing['days'][0]['metrics']);

        $this->assertSame(0, ListingStat::query()->pending()->count());
        $this->assertSame(10, ListingStat::query()->where('event', 'detail_view')->first()->pushed_count);
    }

    public function test_a_second_run_sends_only_what_arrived_since(): void
    {
        $this->fakeCorexAccepts();

        $stat = ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10]);

        $this->artisan('corex:push-stats')->assertSuccessful();

        $stat->increment('count', 4);

        $this->artisan('corex:push-stats')->assertSuccessful();

        $requests = collect(Http::recorded())
            ->filter(fn (array $pair): bool => str_contains($pair[0]->url(), '/listings/stats'))
            ->map(fn (array $pair): array => $pair[0]->data())
            ->values();

        $this->assertCount(2, $requests);
        // Second batch reports only the delta, but the lifetime total catches up.
        $this->assertSame(['detail_view' => 4], $requests[1]['listings'][0]['delta']);
        $this->assertSame(['detail_view' => 14], $requests[1]['listings'][0]['totals']);
    }

    public function test_nothing_is_sent_when_there_is_no_outstanding_delta(): void
    {
        $this->fakeCorexAccepts();

        ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->pushed()->create(['count' => 10]);

        $this->artisan('corex:push-stats')
            ->expectsOutputToContain('No outstanding listing statistics to push.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_rejected_push_leaves_the_delta_outstanding(): void
    {
        Http::fake(['*/listings/stats' => Http::response(['message' => 'nope'], 500)]);

        ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10]);

        $this->artisan('corex:push-stats')->assertFailed();

        $stat = ListingStat::query()->first();
        $this->assertSame(0, $stat->pushed_count);
        $this->assertNull($stat->pushed_at);
        $this->assertSame(1, ListingStat::query()->pending()->count());
    }

    public function test_hits_arriving_during_a_push_stay_outstanding(): void
    {
        $stat = ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10]);

        // A visitor lands on the property while CoreX is answering: the row is
        // already at 11 by the time the delta is marked as sent, so only the 10
        // that were actually reported may be written back.
        Http::fake(function () use ($stat) {
            $stat->newQuery()->whereKey($stat->id)->increment('count');

            return Http::response(['ok' => true]);
        });

        $this->artisan('corex:push-stats')->assertSuccessful();

        $stat->refresh();
        $this->assertSame(11, $stat->count);
        $this->assertSame(10, $stat->pushed_count);
        $this->assertSame(1, $stat->delta());
    }

    public function test_the_push_is_skipped_when_reporting_is_disabled(): void
    {
        $this->fakeCorexAccepts();
        config()->set('services.corex.stats.enabled', false);

        ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10]);

        $this->artisan('corex:push-stats')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(1, ListingStat::query()->pending()->count());
    }

    public function test_a_dry_run_reports_without_sending(): void
    {
        $this->fakeCorexAccepts();

        ListingStat::factory()->forListing('42', ListingStatEvent::DetailView)->create(['count' => 10]);

        $this->artisan('corex:push-stats --dry-run')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(1, ListingStat::query()->pending()->count());
    }

    public function test_prune_drops_old_rows_only_once_corex_has_them(): void
    {
        $this->fakeCorexAccepts();
        config()->set('services.corex.stats.retain_days', 30);

        $old = now()->subDays(60)->toDateString();

        $oldPushed = ListingStat::factory()->forListing('1', ListingStatEvent::DetailView, $old)->pushed()->create(['count' => 5]);
        $oldOutstanding = ListingStat::factory()->forListing('2', ListingStatEvent::DetailView, $old)->create(['count' => 5]);
        $recent = ListingStat::factory()->forListing('3', ListingStatEvent::DetailView)->pushed()->create(['count' => 5]);

        $this->artisan('corex:push-stats --prune')->assertSuccessful();

        // Both stale rows go: the outstanding one was delivered by this very
        // run, which is exactly what makes it safe to drop.
        $this->assertDatabaseMissing('listing_stats', ['id' => $oldPushed->id]);
        $this->assertDatabaseMissing('listing_stats', ['id' => $oldOutstanding->id]);
        $this->assertDatabaseHas('listing_stats', ['id' => $recent->id]);
    }

    public function test_prune_keeps_stale_rows_corex_has_not_accepted(): void
    {
        Http::fake(['*/listings/stats' => Http::response(['message' => 'nope'], 500)]);
        config()->set('services.corex.stats.retain_days', 30);

        $stranded = ListingStat::factory()
            ->forListing('2', ListingStatEvent::DetailView, now()->subDays(60)->toDateString())
            ->create(['count' => 5]);

        $this->artisan('corex:push-stats --prune')->assertFailed();

        // A long CoreX outage must never cost the agency their counts.
        $this->assertDatabaseHas('listing_stats', ['id' => $stranded->id, 'pushed_count' => 0]);
    }
}
