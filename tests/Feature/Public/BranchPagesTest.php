<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The Branches pages are CoreX-backed and gated behind the agency's
 * show.branches feature toggle. When the toggle is off — or the branches feed
 * is unavailable (401/403) — the pages must not exist.
 */
class BranchPagesTest extends TestCase
{
    /** These tests assert on / control the agency payload, so manage their own stub. */
    protected bool $fakeAgency = false;

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function fakeAgency(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Home Finders Coastal',
            'logo_url' => 'https://corex.test/agency-logo.png',
            'contact' => ['phone' => '039 000 0000', 'email' => 'info@hfc.test', 'address' => 'Head Office, Margate'],
            'show' => ['agents' => true, 'listings' => true, 'branches' => true],
        ], $overrides);
    }

    public function test_branches_page_lists_branches_when_the_feature_is_on(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency()]),
            '*/branches*' => Http::response([
                'data' => [[
                    'id' => 12,
                    'trading_name' => 'Coastal Realty Margate',
                    'tagline' => 'Your coast, your home',
                    'address' => '12 Marina Drive, Margate',
                    'phone' => '039 312 0000',
                    'phone_label' => 'Office',
                    'email' => 'margate@coastal.test',
                    'logo_url' => 'https://corex.test/branch-logo.png',
                    'agent_count' => 4,
                    'listing_count' => 17,
                    'agents' => [],
                ]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('branches'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('branches')
                ->has('branches', 1)
                ->where('branches.0.tradingName', 'Coastal Realty Margate')
                ->where('branches.0.agentCount', 4)
                ->where('branches.0.listingCount', 17)
                ->where('branches.0.logo', 'https://corex.test/branch-logo.png')
            );
    }

    public function test_branches_render_in_the_api_order_not_alphabetically(): void
    {
        // CoreX sorts branches server-side per the agency's branch_order_mode.
        // The website must render the array as received and never re-sort it —
        // here the feed is deliberately not alphabetical.
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency()]),
            '*/branches*' => Http::response([
                'data' => [
                    ['id' => 1, 'trading_name' => 'Zinkwazi', 'agent_count' => 0, 'listing_count' => 0, 'agents' => []],
                    ['id' => 2, 'trading_name' => 'Margate', 'agent_count' => 0, 'listing_count' => 0, 'agents' => []],
                    ['id' => 3, 'trading_name' => 'Amanzimtoti', 'agent_count' => 0, 'listing_count' => 0, 'agents' => []],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('branches'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('branches', 3)
                ->where('branches.0.tradingName', 'Zinkwazi')
                ->where('branches.1.tradingName', 'Margate')
                ->where('branches.2.tradingName', 'Amanzimtoti')
            );
    }

    public function test_blank_branch_fields_fall_back_to_agency_defaults(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency()]),
            '*/branches*' => Http::response([
                'data' => [[
                    'id' => 5,
                    'trading_name' => 'Sparse Office',
                    'agent_count' => 0,
                    'listing_count' => 0,
                    'agents' => [],
                    // no logo_url / phone / email / address → use agency defaults
                ]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('branches'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('branches.0.logo', 'https://corex.test/agency-logo.png')
                ->where('branches.0.phone', '039 000 0000')
                ->where('branches.0.email', 'info@hfc.test')
                ->where('branches.0.address', 'Head Office, Margate')
                ->where('branches.0.tagline', null)
            );
    }

    public function test_branches_page_is_hidden_when_the_feature_is_off(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency([
                'show' => ['agents' => true, 'listings' => true, 'branches' => false],
            ])]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('branches'))->assertNotFound();
    }

    public function test_branches_page_is_hidden_when_corex_is_unavailable(): void
    {
        Http::fake(['*' => Http::response(['message' => 'forbidden'], 403)]);

        $this->get(route('branches'))->assertNotFound();
    }

    public function test_branch_detail_shows_its_agents_and_listings(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency()]),
            '*/branches/12' => Http::response(['data' => [
                'id' => 12,
                'trading_name' => 'Coastal Realty Margate',
                'agent_count' => 1,
                'listing_count' => 1,
                'agents' => [[
                    'id' => 88,
                    'name' => 'Thandi Mbeki',
                    'designation' => 'Principal Property Practitioner',
                    'email' => 'thandi@coastal.test',
                    'cell' => '+27 82 000 0000',
                    'photo_url' => 'https://corex.test/agent.jpg',
                ]],
            ]]),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 1, 'title' => 'Margate Beach House', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Margate'],
                ],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->get(route('branches.show', 12))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('branch')
                ->where('branch.tradingName', 'Coastal Realty Margate')
                ->has('branch.agents', 1)
                ->where('branch.agents.0.name', 'Thandi Mbeki')
                ->has('listings', 1)
                ->where('listings.0.title', 'Margate Beach House')
            );
    }

    public function test_branch_detail_returns_404_for_unknown_branch(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency()]),
            '*/branches/*' => Http::response(['message' => 'Not found.'], 404),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('branches.show', 999))->assertNotFound();
    }

    public function test_branch_detail_is_hidden_when_the_feature_is_off(): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => $this->fakeAgency([
                'show' => ['agents' => true, 'listings' => true, 'branches' => false],
            ])]),
            '*' => Http::response(['data' => [], 'meta' => ['last_page' => 1]]),
        ]);

        $this->get(route('branches.show', 12))->assertNotFound();
    }
}
