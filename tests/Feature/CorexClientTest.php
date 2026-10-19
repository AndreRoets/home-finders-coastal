<?php

namespace Tests\Feature;

use App\Services\Corex\CorexClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CorexClientTest extends TestCase
{
    protected function makeClient(int $cacheTtl = 0): CorexClient
    {
        return new CorexClient(
            baseUrl: 'https://corex.test/api/v1/website',
            apiKey: 'test-key',
            timeout: 5,
            cacheTtl: $cacheTtl,
        );
    }

    public function test_it_sends_the_bearer_token_and_unwraps_data(): void
    {
        Http::fake([
            'corex.test/*' => Http::response([
                'data' => [['id' => 1], ['id' => 2]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $listings = $this->makeClient()->listings();

        $this->assertCount(2, $listings);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/listings')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_it_follows_pagination_across_pages(): void
    {
        Http::fake([
            'corex.test/*page=1*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 2]]),
            'corex.test/*page=2*' => Http::response(['data' => [['id' => 2]], 'meta' => ['last_page' => 2]]),
        ]);

        $this->assertCount(2, $this->makeClient()->listings());
        Http::assertSentCount(2);
    }

    public function test_it_fetches_the_remaining_pages_concurrently_in_order(): void
    {
        Http::fake([
            'corex.test/*page=1*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 3]]),
            'corex.test/*page=2*' => Http::response(['data' => [['id' => 2]], 'meta' => ['last_page' => 3]]),
            'corex.test/*page=3*' => Http::response(['data' => [['id' => 3]], 'meta' => ['last_page' => 3]]),
        ]);

        $listings = $this->makeClient()->listings();

        $this->assertSame([1, 2, 3], array_column($listings, 'id'));
        Http::assertSentCount(3);
    }

    public function test_a_single_page_collection_makes_one_request(): void
    {
        Http::fake(['corex.test/*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 1]])]);

        $this->assertCount(1, $this->makeClient()->listings());
        Http::assertSentCount(1);
    }

    public function test_a_truncated_collection_is_not_cached(): void
    {
        Http::fake([
            'corex.test/*page=1*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 2]]),
            'corex.test/*page=2*' => Http::response(['message' => 'boom'], 500),
        ]);

        $client = $this->makeClient(300);

        $this->assertCount(1, $client->listings());
        $this->assertNull(Cache::get('corex:listings:'.md5(serialize([])).':50'));
    }

    public function test_a_complete_collection_is_served_from_cache_on_the_second_call(): void
    {
        Http::fake(['corex.test/*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 1]])]);

        $client = $this->makeClient(300);
        $client->listings();
        $client->listings();

        Http::assertSentCount(1);
    }

    public function test_warm_keeps_the_existing_copy_when_corex_is_down(): void
    {
        $key = 'corex:listings:'.md5(serialize([])).':50';
        Cache::put($key, [['id' => 'old']], 300);

        Http::fake(['corex.test/*' => Http::response(['message' => 'down'], 503)]);

        $this->makeClient(300)->warm();

        $this->assertSame([['id' => 'old']], Cache::get($key));
    }

    public function test_warm_replaces_the_cached_collection_in_place(): void
    {
        $key = 'corex:listings:'.md5(serialize([])).':50';
        Cache::put($key, [['id' => 'old']], 300);

        Http::fake(['corex.test/*' => Http::response(['data' => [['id' => 'new']], 'meta' => ['last_page' => 1]])]);

        $this->assertGreaterThan(0, $this->makeClient(300)->warm());
        $this->assertSame([['id' => 'new']], Cache::get($key));
    }

    public function test_it_follows_pagination_when_last_page_meta_is_absent(): void
    {
        // CoreX returns a full first page but no meta.last_page; the client must
        // keep paging until a short (final) page rather than stopping at page 1
        // — otherwise only the first 50 of the live collection would ever load.
        $fullPage = array_map(static fn (int $i): array => ['id' => $i], range(1, 50));

        Http::fake([
            'corex.test/*page=1*' => Http::response(['data' => $fullPage]),
            'corex.test/*page=2*' => Http::response(['data' => [['id' => 51]]]),
        ]);

        $this->assertCount(51, $this->makeClient()->listings());
        Http::assertSentCount(2);
    }

    public function test_it_fails_soft_on_an_error_response(): void
    {
        Http::fake([
            'corex.test/*' => Http::response(['message' => 'Unauthenticated.'], 401),
        ]);

        $this->assertSame([], $this->makeClient()->listings());
    }

    public function test_ping_reflects_the_response_status(): void
    {
        Http::fake([
            '*/ping' => Http::response('', 200),
        ]);

        $this->assertTrue($this->makeClient()->ping());
    }

    public function test_it_caches_get_responses(): void
    {
        Http::fake([
            'corex.test/*' => Http::response(['data' => [['id' => 1]]]),
        ]);

        $client = $this->makeClient(cacheTtl: 300);
        $client->listings();
        $client->listings();

        Http::assertSentCount(1);
    }

    public function test_it_fetches_testimonials_with_the_bearer_token(): void
    {
        Http::fake([
            'corex.test/*' => Http::response([
                'data' => [['id' => 1, 'author' => 'A'], ['id' => 2, 'author' => 'B']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $testimonials = $this->makeClient()->testimonials();

        $this->assertCount(2, $testimonials);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/testimonials')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_it_passes_the_agent_id_filter_to_testimonials(): void
    {
        Http::fake([
            'corex.test/*' => Http::response(['data' => [['id' => 1]], 'meta' => ['last_page' => 1]]),
        ]);

        $this->makeClient()->testimonials(['agent_id' => 7]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'agent_id=7'));
    }

    public function test_it_fetches_articles_filtered_by_agent_with_the_bearer_token(): void
    {
        Http::fake([
            'corex.test/*' => Http::response([
                'data' => [['id' => 1, 'title' => 'A'], ['id' => 2, 'title' => 'B']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $articles = $this->makeClient()->articles(['agent_id' => 7]);

        $this->assertCount(2, $articles);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/articles')
                && str_contains($request->url(), 'agent_id=7')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }
}
