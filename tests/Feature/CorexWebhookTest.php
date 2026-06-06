<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CorexWebhookTest extends TestCase
{
    protected string $secret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.corex.webhook_secret' => $this->secret]);
    }

    /**
     * Sign a JSON payload the way CoreX does and POST it to the webhook.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function deliver(array $payload, string $event, ?string $signature = null): TestResponse
    {
        $body = json_encode($payload);
        $signature ??= hash_hmac('sha256', $body, $this->secret);

        return $this->call(
            'POST',
            '/api/corex-webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_COREX_EVENT' => $event,
                'HTTP_X_COREX_SIGNATURE' => $signature,
            ],
            $body,
        );
    }

    public function test_a_correctly_signed_delivery_returns_200(): void
    {
        $payload = [
            'event' => 'listing.updated',
            'occurred_at' => '2026-06-02T10:00:00Z',
            'agency_id' => 1,
            'data' => ['id' => 42, 'reference' => 'REF42'],
        ];

        $this->deliver($payload, 'listing.updated')
            ->assertOk()
            ->assertExactJson(['ok' => true]);
    }

    public function test_a_bad_signature_returns_401(): void
    {
        $payload = ['event' => 'listing.updated', 'data' => ['id' => 1]];

        $this->deliver($payload, 'listing.updated', signature: 'deadbeef')
            ->assertUnauthorized();
    }

    public function test_a_missing_signature_returns_401(): void
    {
        $payload = ['event' => 'listing.updated', 'data' => ['id' => 1]];

        $this->deliver($payload, 'listing.updated', signature: '')
            ->assertUnauthorized();
    }

    public function test_a_listing_event_busts_the_listing_caches(): void
    {
        Cache::put('corex:listing:42', ['stale'], 600);
        Cache::put('corex:listing:REF42', ['stale'], 600);
        Cache::put('corex:listings:'.md5(serialize([])).':50', ['stale'], 600);

        $payload = [
            'event' => 'listing.removed',
            'data' => ['id' => 42, 'reference' => 'REF42'],
        ];

        $this->deliver($payload, 'listing.removed')->assertOk();

        $this->assertFalse(Cache::has('corex:listing:42'));
        $this->assertFalse(Cache::has('corex:listing:REF42'));
        $this->assertFalse(Cache::has('corex:listings:'.md5(serialize([])).':50'));
    }

    public function test_an_agent_event_busts_the_agent_caches(): void
    {
        Cache::put('corex:agent:7', ['stale'], 600);
        Cache::put('corex:agents:'.md5(serialize([])).':100', ['stale'], 600);

        $payload = [
            'event' => 'agent.updated',
            'data' => ['id' => 7, 'name' => 'Jane'],
        ];

        $this->deliver($payload, 'agent.updated')->assertOk();

        $this->assertFalse(Cache::has('corex:agent:7'));
        $this->assertFalse(Cache::has('corex:agents:'.md5(serialize([])).':100'));
    }

    public function test_an_agency_event_busts_the_agency_cache(): void
    {
        Cache::put('corex:agency', ['stale'], 600);

        $payload = [
            'event' => 'agency.updated',
            'data' => ['id' => 1, 'name' => 'Home Finders Coastal'],
        ];

        $this->deliver($payload, 'agency.updated')->assertOk();

        $this->assertFalse(Cache::has('corex:agency'));
    }

    public function test_an_article_event_busts_and_repulls_the_agent_article_cache(): void
    {
        // The re-pull hits CoreX — stub it so the test stays offline.
        Http::fake([
            '*/articles*' => Http::response([
                'data' => [['id' => 12, 'agent_id' => 7, 'title' => 'Fresh']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $agentKey = 'corex:articles:'.md5(serialize(['agent_id' => 7])).':50';

        Cache::put('corex:article:12', ['stale'], 600);
        Cache::put('corex:articles:'.md5(serialize([])).':50', ['stale'], 600);
        Cache::put($agentKey, ['stale'], 600);

        $payload = [
            'event' => 'article.published',
            'data' => ['id' => 12, 'agent_id' => 7, 'slug' => 'pre-approval'],
        ];

        $this->deliver($payload, 'article.published')->assertOk();

        // Single-article + agency-wide collection are dropped (lazy re-pull).
        $this->assertFalse(Cache::has('corex:article:12'));
        $this->assertFalse(Cache::has('corex:articles:'.md5(serialize([])).':50'));

        // The agent-scoped list (which backs the profile) is busted AND eagerly
        // re-pulled, so it now holds fresh CoreX data rather than the stale value.
        $this->assertSame([['id' => 12, 'agent_id' => 7, 'title' => 'Fresh']], Cache::get($agentKey));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/articles')
            && str_contains($request->url(), 'agent_id=7'));
    }

    public function test_an_article_removed_event_refreshes_the_agent_articles(): void
    {
        // article.removed carries only { id, agent_id } — still a refresh signal.
        Http::fake([
            '*/articles*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $agentKey = 'corex:articles:'.md5(serialize(['agent_id' => 7])).':50';
        Cache::put($agentKey, [['id' => 12, 'agent_id' => 7]], 600);

        $payload = [
            'event' => 'article.removed',
            'data' => ['id' => 12, 'agent_id' => 7],
        ];

        $this->deliver($payload, 'article.removed')->assertOk();

        // Re-pull reflects the removal: the agent now has an empty article list.
        $this->assertSame([], Cache::get($agentKey));
    }

    public function test_article_webhooks_are_idempotent_on_redelivery(): void
    {
        Http::fake([
            '*/articles*' => Http::response([
                'data' => [['id' => 12, 'agent_id' => 7, 'title' => 'Fresh']],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $agentKey = 'corex:articles:'.md5(serialize(['agent_id' => 7])).':50';

        $payload = [
            'event' => 'article.updated',
            'data' => ['id' => 12, 'agent_id' => 7, 'slug' => 'pre-approval'],
        ];

        // Redelivery: busting + re-pulling twice must be safe and converge.
        $this->deliver($payload, 'article.updated')->assertOk();
        $this->deliver($payload, 'article.updated')->assertOk();

        $this->assertSame([['id' => 12, 'agent_id' => 7, 'title' => 'Fresh']], Cache::get($agentKey));
    }
}
