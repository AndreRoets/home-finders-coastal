<?php

namespace Tests\Feature;

use App\Services\Corex\CorexClient;
use Illuminate\Http\Client\Request;
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
