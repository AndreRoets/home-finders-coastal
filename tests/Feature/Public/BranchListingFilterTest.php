<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * The listing and agent index pages expose an "All + branches" filter when the
 * branches feature is on. Selecting a branch (?branch_id={id}) scopes the CoreX
 * request to it; "All" (or the feature being off) leaves it unscoped.
 */
class BranchListingFilterTest extends TestCase
{
    /** These tests control the agency payload (feature toggle), so manage their own stub. */
    protected bool $fakeAgency = false;

    protected function fakeCorex(bool $branchesOn = true): void
    {
        Http::fake([
            '*/agency*' => Http::response(['data' => [
                'name' => 'Home Finders Coastal',
                'show' => ['agents' => true, 'listings' => true, 'branches' => $branchesOn],
            ]]),
            '*/branches*' => Http::response([
                'data' => [
                    ['id' => 12, 'trading_name' => 'Atlantic Seaboard', 'agent_count' => 1, 'listing_count' => 1, 'agents' => []],
                    ['id' => 13, 'trading_name' => 'Dolphin Coast', 'agent_count' => 1, 'listing_count' => 1, 'agents' => []],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*/listings*' => Http::response([
                'data' => [['id' => 1, 'title' => 'A Home', 'listing_type' => 'sale', 'status' => 'for_sale', 'suburb' => 'Camps Bay']],
                'meta' => ['last_page' => 1],
            ]),
            '*/agents*' => Http::response([
                'data' => [['id' => 88, 'name' => 'Thandi Mbeki', 'cell' => '+27 82 000 0000']],
                'meta' => ['last_page' => 1],
            ]),
        ]);
    }

    public function test_for_sale_exposes_branch_options_and_no_active_branch_by_default(): void
    {
        $this->fakeCorex();

        $this->get(route('for-sale'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('branches', 2)
                ->where('branches.0', ['id' => 12, 'name' => 'Atlantic Seaboard'])
                ->where('activeBranch', null)
            );

        // No branch selected → listings request carries no branch_id.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/listings') && ! str_contains($request->url(), 'branch_id'));
    }

    public function test_for_sale_scopes_the_listings_request_to_the_selected_branch(): void
    {
        $this->fakeCorex();

        $this->get(route('for-sale', ['branch_id' => 12]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('activeBranch', 12));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/listings') && str_contains($request->url(), 'branch_id=12'));
    }

    public function test_an_unknown_branch_id_is_ignored(): void
    {
        $this->fakeCorex();

        $this->get(route('for-sale', ['branch_id' => 999]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('activeBranch', null));

        Http::assertSent(fn ($request) => str_contains($request->url(), '/listings') && ! str_contains($request->url(), 'branch_id'));
    }

    public function test_no_branch_filter_when_the_feature_is_off(): void
    {
        $this->fakeCorex(branchesOn: false);

        $this->get(route('for-sale', ['branch_id' => 12]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('branches', [])
                ->where('activeBranch', null)
            );

        // Feature off → branch_id is not applied to the listings request.
        Http::assertSent(fn ($request) => str_contains($request->url(), '/listings') && ! str_contains($request->url(), 'branch_id'));
    }

    public function test_agents_page_scopes_to_the_selected_branch(): void
    {
        $this->fakeCorex();

        $this->get(route('agents', ['branch_id' => 13]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agents')
                ->has('branches', 2)
                ->where('activeBranch', 13)
            );

        Http::assertSent(fn ($request) => str_contains($request->url(), '/agents') && str_contains($request->url(), 'branch_id=13'));
    }
}
