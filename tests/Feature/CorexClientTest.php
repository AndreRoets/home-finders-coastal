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
}
