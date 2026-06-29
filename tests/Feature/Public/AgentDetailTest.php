<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The agent detail page (/agents/{id}) shows the agent profile, their listings
 * and the testimonials about them. It degrades gracefully when the standalone
 * /agents/{id} endpoint 404s (falling back to the agent embedded on listings)
 * and renders a 404 "not found" page when nothing resolves.
 */
class AgentDetailTest extends TestCase
{
    public function test_it_shows_the_agent_with_listings_and_testimonials(): void
    {
        Http::fake([
            '*/agents/7' => Http::response(['data' => [
                'id' => 7,
                'name' => 'Thandi Mbeki',
                'designation' => 'Principal Property Practitioner',
                'cell' => '+27 82 000 0000',
                'email' => 'thandi@example.test',
                'photo_url' => 'https://corex.test/agent.jpg',
            ]]),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'Sea View Villa', 'listing_type' => 'sale', 'status' => 'for_sale', 'price_display' => 'R 5,000,000', 'suburb' => 'Clifton'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/testimonials*' => Http::response([
                'data' => [
                    ['id' => 99, 'author' => 'Happy Buyer', 'rating' => 5, 'body' => 'Brilliant.', 'agent_id' => 7],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents.show', 7))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->where('agent.name', 'Thandi Mbeki')
                ->where('agent.designation', 'Principal Property Practitioner')
                ->has('listings', 1)
                ->where('listings.0.title', 'Sea View Villa')
                ->has('testimonials', 1)
                ->where('testimonials.0.author', 'Happy Buyer')
            );
    }

    public function test_it_falls_back_to_the_agent_embedded_on_listings_when_endpoint_404s(): void
    {
        Http::fake([
            '*/agents/29' => Http::response(['message' => 'Not found.'], 404),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'Margate Flat', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Margate',
                        'agent' => ['id' => 29, 'name' => 'Maggie Venter', 'photo_url' => 'https://corex.test/m.jpg']],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/testimonials*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('agents.show', 29))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->where('agent.name', 'Maggie Venter')
                ->has('listings', 1)
            );
    }

    public function test_it_renders_a_graceful_404_when_the_agent_is_unknown(): void
    {
        Http::fake([
            '*/agents/*' => Http::response(['message' => 'Not found.'], 404),
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
            '*/testimonials*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('agents.show', 12345))
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agent')
                ->where('agent', null)
            );
    }

    public function test_listing_cards_expose_the_linked_agent(): void
    {
        Http::fake([
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'For Sale Home', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Camps Bay',
                        'agent' => ['id' => 7, 'name' => 'Thandi Mbeki', 'designation' => 'Principal', 'photo_url' => 'https://corex.test/a.jpg']],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('listings.data.0.agent.id', 7)
                ->where('listings.data.0.agent.name', 'Thandi Mbeki')
                ->where('listings.data.0.agent.designation', 'Principal')
                ->where('listings.data.0.agent.photo', 'https://corex.test/a.jpg')
            );
    }
}
