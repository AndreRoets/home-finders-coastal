<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
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

    public function test_an_article_event_busts_the_article_caches(): void
    {
        Cache::put('corex:article:12', ['stale'], 600);
        Cache::put('corex:articles:'.md5(serialize([])).':50', ['stale'], 600);

        $payload = [
            'event' => 'article.published',
            'data' => ['id' => 12, 'slug' => 'pre-approval'],
        ];

        $this->deliver($payload, 'article.published')->assertOk();

        $this->assertFalse(Cache::has('corex:article:12'));
        $this->assertFalse(Cache::has('corex:articles:'.md5(serialize([])).':50'));
    }
}
