<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    protected function page(array $attributes = []): Page
    {
        return Page::query()->create(array_merge([
            'key' => 'contact',
            'name' => 'Contact Us',
            'slug' => 'contact',
        ], $attributes));
    }

    public function test_guests_cannot_reach_the_pages_panel(): void
    {
        $this->get('/admin/pages')->assertRedirect('/login');
    }

    public function test_admin_can_view_the_pages_list(): void
    {
        $this->actingAsAdmin();
        $this->page();

        $this->get('/admin/pages')->assertOk();
    }

    public function test_updating_seo_meta_persists(): void
    {
        $this->actingAsAdmin();
        $page = $this->page();

        $this->put("/admin/pages/{$page->id}", [
            'name' => 'Contact Us',
            'slug' => 'contact',
            'is_active' => true,
            'meta_title' => 'Talk to us | HFC',
            'meta_description' => 'Reach the coastal team.',
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'sitemap_priority' => '0.5',
            'sitemap_frequency' => 'monthly',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'meta_title' => 'Talk to us | HFC',
            'meta_description' => 'Reach the coastal team.',
        ]);
    }

    public function test_an_uploaded_share_image_is_saved_and_rendered_on_the_page(): void
    {
        $this->actingAsAdmin();
        $page = $this->page();
        $shareImage = url('/media/uploads/share-banner-a1b2c3d4.jpg');

        $this->put("/admin/pages/{$page->id}", [
            'name' => 'Contact Us',
            'slug' => 'contact',
            'is_active' => true,
            'og_image' => $shareImage,
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'sitemap_priority' => '0.5',
            'sitemap_frequency' => 'monthly',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'og_image' => $shareImage]);

        $this->get('/contact')
            ->assertSee('<meta property="og:image" content="'.$shareImage.'">', false)
            ->assertSee('<meta name="twitter:image" content="'.$shareImage.'">', false);
    }

    public function test_the_page_editor_exposes_the_site_wide_default_share_image(): void
    {
        $this->actingAsAdmin();
        $page = $this->page();
        SiteSetting::current()->update(['default_og_image' => 'https://hfcoastal.co.za/media/uploads/default.jpg']);

        $this->get("/admin/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $view) => $view
                ->component('admin/pages/edit')
                ->where('defaultOgImage', 'https://hfcoastal.co.za/media/uploads/default.jpg')
            );
    }

    public function test_changing_the_slug_creates_a_redirect_from_the_old_slug(): void
    {
        $this->actingAsAdmin();
        $page = $this->page(['slug' => 'contact']);

        $this->put("/admin/pages/{$page->id}", [
            'name' => 'Contact Us',
            'slug' => 'contact-us',
            'is_active' => true,
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'sitemap_priority' => '0.5',
            'sitemap_frequency' => 'monthly',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => 'contact-us']);
        $this->assertDatabaseHas('page_redirects', [
            'page_id' => $page->id,
            'old_slug' => 'contact',
            'status_code' => 301,
        ]);
    }

    public function test_reserved_slugs_are_rejected(): void
    {
        $this->actingAsAdmin();
        $page = $this->page();

        $this->put("/admin/pages/{$page->id}", [
            'name' => 'Contact Us',
            'slug' => 'admin',
            'is_active' => true,
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'sitemap_priority' => '0.5',
            'sitemap_frequency' => 'monthly',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => 'contact']);
    }

    public function test_invalid_json_ld_is_rejected(): void
    {
        $this->actingAsAdmin();
        $page = $this->page();

        $this->put("/admin/pages/{$page->id}", [
            'name' => 'Contact Us',
            'slug' => 'contact',
            'is_active' => true,
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'json_ld' => '{not valid json}',
            'sitemap_priority' => '0.5',
            'sitemap_frequency' => 'monthly',
        ])->assertSessionHasErrors('json_ld');
    }
}
