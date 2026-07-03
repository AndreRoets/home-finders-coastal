<?php

namespace Tests\Feature\Admin;

use App\Models\ListingSeo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Per-listing SEO overrides for the dynamic, CoreX-backed property pages,
 * managed from the /admin/pages screen.
 */
class PropertySeoTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeListings(): void
    {
        Http::fake([
            '*/listings/55*' => Http::response(['data' => [
                'id' => 55,
                'title' => 'Seaside Villa',
                'description' => 'A sun-drenched four bedroom villa moments from the beach.',
                'listing_type' => 'sale',
                'status' => 'for_sale',
            ]]),
            '*/listings*' => Http::response([
                'data' => [
                    ['id' => 55, 'title' => 'Seaside Villa', 'listing_type' => 'sale', 'status' => 'for_sale'],
                    ['id' => 56, 'title' => 'Mountain Retreat', 'listing_type' => 'sale', 'status' => 'for_sale'],
                ],
                'meta' => ['last_page' => 1],
            ]),
            '*' => Http::response(['data' => []]),
        ]);
    }

    public function test_pages_index_lists_live_properties_for_seo_editing(): void
    {
        $this->fakeListings();

        $this->actingAs(User::factory()->create())
            ->get('/admin/pages')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/pages/index')
                ->has('properties.data', 2)
                ->where('properties.data.0.title', 'Seaside Villa')
                ->where('properties.data.0.customised', false)
            );
    }

    public function test_property_list_can_be_searched(): void
    {
        $this->fakeListings();

        $this->actingAs(User::factory()->create())
            ->get('/admin/pages?property_search=mountain')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('properties.data', 1)
                ->where('properties.data.0.title', 'Mountain Retreat')
            );
    }

    public function test_property_seo_override_can_be_saved(): void
    {
        $this->fakeListings();

        $this->actingAs(User::factory()->create())
            ->put('/admin/properties/55', [
                'meta_title' => 'Luxury Seaside Villa for Sale',
                'meta_description' => 'A hand-picked beachfront home.',
                'canonical_url' => 'https://hfcoastal.co.za/property/seaside-villa-55',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('listing_seo', [
            'listing_id' => '55',
            'meta_title' => 'Luxury Seaside Villa for Sale',
        ]);
    }

    public function test_saved_override_is_applied_to_the_public_property_meta(): void
    {
        $this->fakeListings();

        ListingSeo::create([
            'listing_id' => '55',
            'meta_title' => 'Custom Override Title',
            'meta_description' => 'Custom override description for the listing.',
        ]);

        $response = $this->get(route('property.show', 'seaside-villa-55'));

        $response->assertOk();
        $response->assertSee('Custom Override Title', false);
        $response->assertSee('Custom override description for the listing.', false);
    }

    public function test_property_seo_can_be_marked_done(): void
    {
        $this->fakeListings();

        $this->actingAs(User::factory()->create())
            ->put('/admin/properties/55', [
                'meta_title' => 'Luxury Seaside Villa for Sale',
                'meta_description' => 'A hand-picked beachfront home.',
                'canonical_url' => 'https://hfcoastal.co.za/property/seaside-villa-55',
                'is_done' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('listing_seo', [
            'listing_id' => '55',
            'is_done' => true,
        ]);
    }

    public function test_done_status_is_surfaced_on_the_pages_index(): void
    {
        $this->fakeListings();

        ListingSeo::create(['listing_id' => '55', 'is_done' => true]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/pages')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('properties.data.0.done', true)
                ->where('properties.data.1.done', false)
            );
    }

    public function test_property_seo_editor_requires_authentication(): void
    {
        $this->get('/admin/properties/55/edit')->assertRedirect('/login');
    }
}
