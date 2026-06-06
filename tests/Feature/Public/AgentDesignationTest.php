<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * CoreX returns a `designation` (role/title) on every agent, which the cards
 * render as a muted sub-line beneath the name. It may be null for some agents,
 * in which case it must collapse to null so the frontend hides the sub-line
 * entirely — never an empty string or fallback label.
 */
class AgentDesignationTest extends TestCase
{
    public function test_agents_page_exposes_designation_and_nulls_when_absent(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Thandi Mbeki', 'designation' => 'Principal Property Practitioner', 'cell' => '1'],
                    ['id' => 2, 'name' => 'Bob Marais', 'designation' => null, 'cell' => '2'],
                    ['id' => 3, 'name' => 'Aisha Patel', 'designation' => '  ', 'cell' => '3'],
                    ['id' => 4, 'name' => 'Sipho Dlamini', 'cell' => '4'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('agents'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agents')
                ->where('agents.0.designation', 'Principal Property Practitioner')
                ->where('agents.1.designation', null)
                // Whitespace-only collapses to null so the card hides the line.
                ->where('agents.2.designation', null)
                // Missing key maps to null, never an empty string.
                ->where('agents.3.designation', null)
            );
    }

    public function test_home_strip_exposes_agent_designation(): void
    {
        Http::fake([
            '*/agents*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Thandi Mbeki', 'designation' => 'Candidate Property Practitioner', 'cell' => '1'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/listings*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home')
                ->where('agents.0.designation', 'Candidate Property Practitioner')
            );
    }
}
