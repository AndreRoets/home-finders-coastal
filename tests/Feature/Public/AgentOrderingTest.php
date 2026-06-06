<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * CoreX is the single source of truth for agent order: its /agents endpoint
 * already returns agents sorted (alphabetical, or custom with numbered agents
 * first then the rest A–Z). The site must render that array exactly as
 * received and must NEVER re-sort it. These tests lock that guarantee in so a
 * future ->sortBy()/usort()/JS .sort() reintroduction fails CI.
 */
class AgentOrderingTest extends TestCase
{
    /**
     * "Custom" mode with a single numbered agent: CoreX returns that agent
     * first, then the rest A–Z. We must mirror the array verbatim — note Bob
     * is first even though Alice would precede him alphabetically, proving we
     * do not alphabetise the response ourselves.
     */
    public function test_agents_page_renders_agents_in_exact_corex_order(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [
                    ['id' => 2, 'name' => 'Bob Marais', 'email' => 'bob@example.test', 'cell' => '+27 82 000 0002'],
                    ['id' => 1, 'name' => 'Alice Adams', 'email' => 'alice@example.test', 'cell' => '+27 82 000 0001'],
                    ['id' => 3, 'name' => 'Charlie Cole', 'email' => 'charlie@example.test', 'cell' => '+27 82 000 0003'],
                    ['id' => 4, 'name' => 'Dora Daniels', 'email' => 'dora@example.test', 'cell' => '+27 82 000 0004'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agents')
                ->has('agents', 4)
                ->where('agents.0.name', 'Bob Marais')
                ->where('agents.1.name', 'Alice Adams')
                ->where('agents.2.name', 'Charlie Cole')
                ->where('agents.3.name', 'Dora Daniels')
            );
    }

    /**
     * Switching CoreX to "alphabetical" simply means /agents arrives A–Z; we
     * render it as-is. (Same code path — the site never inspects the mode.)
     */
    public function test_agents_page_renders_alphabetical_order_as_received(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Alice Adams', 'cell' => '1'],
                    ['id' => 2, 'name' => 'Bob Marais', 'cell' => '2'],
                    ['id' => 3, 'name' => 'Charlie Cole', 'cell' => '3'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('agents.0.name', 'Alice Adams')
                ->where('agents.1.name', 'Bob Marais')
                ->where('agents.2.name', 'Charlie Cole')
            );
    }

    /**
     * Paginated agents must be concatenated page 1 → page 2 in order, never
     * merged-and-resorted. Page 1 holds "Zara" and page 2 "Aaron"; the result
     * stays [Zara, Aaron], which only holds if we preserve page order.
     */
    public function test_agents_page_concatenates_pages_preserving_order(): void
    {
        Http::fakeSequence('*/agents*')
            ->push([
                'data' => [['id' => 9, 'name' => 'Zara Page-One', 'cell' => '9']],
                'meta' => ['last_page' => 2],
            ])
            ->push([
                'data' => [['id' => 1, 'name' => 'Aaron Page-Two', 'cell' => '1']],
                'meta' => ['last_page' => 2],
            ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('agents', 2)
                ->where('agents.0.name', 'Zara Page-One')
                ->where('agents.1.name', 'Aaron Page-Two')
            );
    }

    /**
     * The home "Meet the team" strip pulls from the same /agents endpoint and
     * must likewise preserve CoreX order (numbered agent first, then A–Z).
     */
    public function test_home_page_renders_agents_in_exact_corex_order(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [
                    ['id' => 2, 'name' => 'Bob Marais', 'cell' => '2'],
                    ['id' => 1, 'name' => 'Alice Adams', 'cell' => '1'],
                    ['id' => 3, 'name' => 'Charlie Cole', 'cell' => '3'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home')
                ->where('agents.0.name', 'Bob Marais')
                ->where('agents.1.name', 'Alice Adams')
                ->where('agents.2.name', 'Charlie Cole')
            );
    }
}
