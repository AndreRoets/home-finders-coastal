<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTrimTest extends TestCase
{
    /**
     * A 100x100 JPEG with a 20px solid-white border framing a 60x60 red block —
     * stands in for a letterboxed import photo.
     */
    private function letterboxedJpeg(): string
    {
        $image = imagecreatetruecolor(100, 100);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        imagefilledrectangle($image, 20, 20, 79, 79, imagecolorallocate($image, 200, 30, 30));

        ob_start();
        imagejpeg($image, null, 92);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function test_it_trims_the_white_border_off_an_allowed_host_image(): void
    {
        Storage::fake('local');
        Http::fake(['staging.corexos.co.za/*' => Http::response($this->letterboxedJpeg())]);

        $url = 'https://staging.corexos.co.za/storage/properties/697/sold-import-primary.jpg';

        $response = $this->get(route('media.trimmed', ['u' => $url]));

        $response->assertOk();

        $path = 'trimmed/'.sha1($url).'.jpg';
        Storage::disk('local')->assertExists($path);

        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($path));
        $this->assertLessThan(100, $width, 'White border should have been trimmed horizontally.');
        $this->assertLessThan(100, $height, 'White border should have been trimmed vertically.');
    }

    public function test_a_cached_image_is_not_refetched(): void
    {
        Storage::fake('local');
        $url = 'https://staging.corexos.co.za/storage/properties/1/photo.jpg';
        Storage::disk('local')->put('trimmed/'.sha1($url).'.jpg', $this->letterboxedJpeg());

        Http::fake();

        $this->get(route('media.trimmed', ['u' => $url]))->assertOk();

        Http::assertNothingSent();
    }

    public function test_it_rejects_a_host_that_is_not_allow_listed(): void
    {
        $this->get(route('media.trimmed', ['u' => 'https://evil.example.com/internal.jpg']))
            ->assertNotFound();
    }

    public function test_it_rejects_a_missing_url(): void
    {
        $this->get(route('media.trimmed'))->assertNotFound();
    }

    public function test_it_falls_back_to_the_original_when_the_source_fails(): void
    {
        Storage::fake('local');
        $url = 'https://staging.corexos.co.za/storage/properties/2/photo.jpg';
        Http::fake(['staging.corexos.co.za/*' => Http::response('', 500)]);

        $this->get(route('media.trimmed', ['u' => $url]))
            ->assertRedirect($url);
    }
}
