<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin image uploads used for social share cards (og:image / twitter:image),
 * and the public route social crawlers fetch them from.
 */
class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_image_can_be_uploaded_and_returns_its_public_url(): void
    {
        Storage::fake('public');

        $response = $this->actingAs(User::factory()->create())
            ->post('/admin/media', ['file' => UploadedFile::fake()->image('Home Page Banner.jpg', 1200, 630)]);

        $response->assertOk();

        $url = $response->json('url');
        $name = $response->json('name');

        $this->assertStringContainsString('/media/uploads/', $url);
        $this->assertStringStartsWith('home-page-banner-', $name);
        Storage::disk('public')->assertExists('seo/'.$name);
    }

    public function test_a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->post('/admin/media', ['file' => UploadedFile::fake()->create('brochure.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_uploading_requires_authentication(): void
    {
        $this->post('/admin/media', ['file' => UploadedFile::fake()->image('banner.jpg')])
            ->assertRedirect('/login');
    }

    public function test_an_uploaded_image_is_served_publicly(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('seo/banner.jpg', 'fake-image-bytes');

        $this->get('/media/uploads/banner.jpg')
            ->assertOk()
            ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    }

    public function test_a_missing_upload_returns_not_found(): void
    {
        Storage::fake('public');

        $this->get('/media/uploads/nothing-here.jpg')->assertNotFound();
    }
}
