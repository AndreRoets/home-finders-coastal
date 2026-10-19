<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CorexWarmCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.corex.demo' => false,
            'services.corex.base_url' => 'https://corex.test/api/v1/website',
            'services.corex.api_key' => 'test-key',
            'services.corex.cache_ttl' => 300,
            'services.corex.media_hosts' => ['media.test'],
        ]);
    }

    private function jpeg(): string
    {
        $image = imagecreatetruecolor(40, 30);
        imagefill($image, 0, 0, imagecolorallocate($image, 20, 120, 60));

        ob_start();
        imagejpeg($image, null, 90);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function test_warm_cache_stores_the_listings_collection(): void
    {
        Http::fake(['corex.test/*' => Http::response(['data' => [['id' => 7]], 'meta' => ['last_page' => 1]])]);

        $this->artisan('corex:warm-cache')->assertSuccessful();

        $this->assertSame([['id' => 7]], Cache::get('corex:listings:'.md5(serialize([])).':50'));
    }

    public function test_warm_cache_prunes_expired_rows_from_the_database_cache(): void
    {
        config(['cache.default' => 'database']);
        Cache::purge('database');
        Http::fake(['corex.test/*' => Http::response([], 503)]);

        DB::table('cache')->insert([
            ['key' => 'expired-one', 'value' => 's:1:"x";', 'expiration' => now()->subHour()->getTimestamp()],
            ['key' => 'still-valid', 'value' => 's:1:"y";', 'expiration' => now()->addHour()->getTimestamp()],
        ]);

        $this->artisan('corex:warm-cache')->assertSuccessful();

        $this->assertDatabaseMissing('cache', ['key' => 'expired-one']);
        $this->assertDatabaseHas('cache', ['key' => 'still-valid']);
    }

    public function test_warm_images_downloads_covers_first_and_respects_the_limit(): void
    {
        Storage::fake('local');
        Http::fake([
            'corex.test/*' => Http::response([
                'data' => [
                    ['id' => 1, 'images' => ['https://media.test/a-cover.jpg', 'https://media.test/a-2.jpg']],
                    ['id' => 2, 'images' => ['https://media.test/b-cover.jpg', 'https://elsewhere.test/b-2.jpg']],
                ],
                'meta' => ['last_page' => 1],
            ]),
            'media.test/*' => Http::response($this->jpeg()),
        ]);

        $this->artisan('corex:warm-images --limit=2')->assertSuccessful();

        // Both covers, neither second photo — and nothing from a non-allow-listed host.
        Storage::disk('local')->assertExists('trimmed/'.sha1('https://media.test/a-cover.jpg').'.jpg');
        Storage::disk('local')->assertExists('trimmed/'.sha1('https://media.test/b-cover.jpg').'.jpg');
        Storage::disk('local')->assertMissing('trimmed/'.sha1('https://media.test/a-2.jpg').'.jpg');
    }

    public function test_warm_images_skips_photos_that_are_already_cached(): void
    {
        Storage::fake('local');
        $cached = 'https://media.test/cached.jpg';
        Storage::disk('local')->put('trimmed/'.sha1($cached).'.jpg', $this->jpeg());

        Http::fake([
            'corex.test/*' => Http::response([
                'data' => [['id' => 1, 'images' => [$cached]]],
                'meta' => ['last_page' => 1],
            ]),
        ]);

        $this->artisan('corex:warm-images')->assertSuccessful();

        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'media.test'));
    }
}
