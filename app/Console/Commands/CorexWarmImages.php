<?php

namespace App\Console\Commands;

use App\Services\Corex\CorexClient;
use App\Services\Media\TrimmedImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Download and white-border-trim listing photos ahead of time, so the first
 * visitor to a new listing isn't the one waiting on CoreX and the image
 * processing. Cover photos go first, then the rest of each gallery; each run is
 * capped (`--limit`) so it stays short, and the backlog drains across runs.
 *
 * Run as the cache owner (www-data) so the files stay writable by PHP-FPM:
 *
 *   php artisan corex:warm-images --limit=100
 */
class CorexWarmImages extends Command
{
    /**
     * @var string
     */
    protected $signature = 'corex:warm-images {--limit=100 : Maximum number of photos to download per run}';

    /**
     * @var string
     */
    protected $description = 'Pre-fetch and trim listing photos that are not cached yet';

    public function handle(CorexClient $corex, TrimmedImageStore $images): int
    {
        $lock = Cache::lock('lock:corex:warm-images', 900);

        if (! $lock->get()) {
            $this->warn('Another image warm run is in progress; skipping.');

            return self::SUCCESS;
        }

        try {
            $limit = max(1, (int) $this->option('limit'));
            $fetched = 0;
            $failed = 0;

            foreach ($this->pendingUrls($corex, $images) as $url) {
                if ($fetched + $failed >= $limit) {
                    break;
                }

                $images->original($url) === null ? $failed++ : $fetched++;
            }

            $this->info("Warmed {$fetched} photo".($fetched === 1 ? '' : 's').", {$failed} failed.");
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    /**
     * Photo URLs that are on an allow-listed media host and not cached yet,
     * covers first.
     *
     * @return \Generator<int, string>
     */
    protected function pendingUrls(CorexClient $corex, TrimmedImageStore $images): \Generator
    {
        $listings = $corex->listings();

        foreach ([true, false] as $coversOnly) {
            foreach ($listings as $listing) {
                $photos = array_values(array_filter(
                    (array) Arr::get($listing, 'images', []),
                    static fn ($photo): bool => is_string($photo) && $photo !== '',
                ));

                foreach ($coversOnly ? array_slice($photos, 0, 1) : array_slice($photos, 1) as $photo) {
                    // Same normalisation the listing mapper applies before it
                    // builds the proxy URL, so the cache key matches.
                    $url = str_replace('/storage/storage/', '/storage/', $photo);

                    if ($images->isAllowedHost($url) && ! $images->hasOriginal($url)) {
                        yield $url;
                    }
                }
            }
        }
    }
}
