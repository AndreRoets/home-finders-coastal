<?php

namespace App\Services\Stats;

use App\Console\Commands\CorexPushStats;
use App\Models\ListingStat;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Counts listing engagement locally so it can be pushed back to CoreX in
 * batches by {@see CorexPushStats}.
 *
 * Writes are cheap (one to three queries) and always fail soft: a stats write
 * must never break a public page, so every entry point swallows and logs.
 *
 * Nothing identifying is stored. Uniqueness is decided by a short-lived,
 * salted hash of the visitor IP and user agent held in the cache only.
 */
class ListingStatsRecorder
{
    /**
     * How long a visitor's detail-page view is remembered before the same
     * visitor counts as unique again (six hours — long enough to collapse a
     * browsing session, short enough that a genuine return visit is counted).
     */
    public const UNIQUE_VIEW_TTL = 6 * 3600;

    /**
     * Substrings that mark a user agent as a crawler. Bots still get the page,
     * they just do not inflate the agency's numbers.
     */
    protected const CRAWLER_TOKENS = [
        'bot', 'crawl', 'spider', 'slurp', 'facebookexternalhit', 'preview',
        'headless', 'lighthouse', 'pingdom', 'monitor', 'curl', 'wget',
        'python-requests', 'go-http-client', 'okhttp', 'axios', 'postman',
    ];

    /**
     * Record a property detail page view ("views"), plus a unique view the
     * first time this visitor lands on the listing within the dedupe window.
     * Crawlers are ignored entirely.
     */
    public function recordDetailView(int|string $listingId, ?string $reference, Request $request): void
    {
        if ($this->isCrawler($request->userAgent())) {
            return;
        }

        $this->record($listingId, $reference, ListingStatEvent::DetailView);

        if ($this->isFirstViewInWindow($listingId, $request)) {
            $this->record($listingId, $reference, ListingStatEvent::UniqueDetailView);
        }
    }

    /**
     * Record a single engagement event for one listing.
     */
    public function record(int|string $listingId, ?string $reference, ListingStatEvent $event, int $count = 1): void
    {
        $listingId = (string) $listingId;

        if ($listingId === '' || $count < 1) {
            return;
        }

        try {
            $this->increment($listingId, $reference, $event, $count);
        } catch (Throwable $e) {
            Log::warning('Listing stat write failed', [
                'listing_id' => $listingId,
                'event' => $event->value,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record one event across many listings at once — used for the impressions
     * ("hits") logged when a results page renders. Costs three queries no
     * matter how many listings are on the page.
     *
     * @param  array<int|string, string|null>  $references  CoreX listing id => reference
     */
    public function recordMany(array $references, ListingStatEvent $event, Request $request): void
    {
        if ($references === [] || $this->isCrawler($request->userAgent())) {
            return;
        }

        try {
            $this->incrementMany($references, $event);
        } catch (Throwable $e) {
            Log::warning('Listing stat bulk write failed', [
                'event' => $event->value,
                'listings' => count($references),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Increment today's counter, creating the row on the first hit of the day.
     *
     * The update-then-insert order (rather than firstOrCreate-then-update) keeps
     * the common path to a single query. A concurrent request can win the race
     * to insert between our two statements, which surfaces as a unique
     * constraint violation — retrying the increment then folds our count into
     * the row it created, so no hit is lost.
     */
    protected function increment(string $listingId, ?string $reference, ListingStatEvent $event, int $count): void
    {
        $attributes = [
            'listing_id' => $listingId,
            'event' => $event->value,
            'date' => now()->toDateString(),
        ];

        // Keep the reference fresh when we know it, but never overwrite a
        // stored reference with a null from a caller that did not have one.
        $extra = $reference !== null && $reference !== '' ? ['reference' => $reference] : [];

        if (ListingStat::query()->where($attributes)->increment('count', $count, $extra) > 0) {
            return;
        }

        try {
            ListingStat::query()->create($attributes + ['reference' => $reference, 'count' => $count]);
        } catch (UniqueConstraintViolationException) {
            ListingStat::query()->where($attributes)->increment('count', $count, $extra);
        }
    }

    /**
     * Bulk variant of {@see self::increment()}: bump every listing that already
     * has a row for today, then insert the rest. `insertOrIgnore` absorbs the
     * race where a concurrent request created the same missing row.
     *
     * @param  array<int|string, string|null>  $references
     */
    protected function incrementMany(array $references, ListingStatEvent $event): void
    {
        $date = now()->toDateString();
        $listingIds = array_values(array_filter(
            array_map('strval', array_keys($references)),
            static fn (string $id): bool => $id !== '',
        ));

        if ($listingIds === []) {
            return;
        }

        $existing = ListingStat::query()
            ->where('event', $event->value)
            ->where('date', $date)
            ->whereIn('listing_id', $listingIds)
            ->pluck('listing_id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();

        if ($existing !== []) {
            ListingStat::query()
                ->where('event', $event->value)
                ->where('date', $date)
                ->whereIn('listing_id', $existing)
                ->increment('count');
        }

        $missing = array_values(array_diff($listingIds, $existing));

        if ($missing === []) {
            return;
        }

        $now = now();

        ListingStat::query()->insertOrIgnore(array_map(static fn (string $listingId): array => [
            'listing_id' => $listingId,
            'reference' => $references[$listingId] ?? null,
            'event' => $event->value,
            'date' => $date,
            'count' => 1,
            'pushed_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $missing));
    }

    /**
     * Whether this visitor has not been seen on this listing inside the dedupe
     * window. `Cache::add` is atomic, so exactly one concurrent request wins.
     */
    protected function isFirstViewInWindow(int|string $listingId, Request $request): bool
    {
        $fingerprint = hash_hmac(
            'sha256',
            implode('|', [$request->ip(), $request->userAgent(), $listingId]),
            (string) config('app.key'),
        );

        return Cache::add('listing-view:'.$fingerprint, true, self::UNIQUE_VIEW_TTL);
    }

    /**
     * Whether the user agent looks like a crawler, scraper or uptime monitor.
     * A missing user agent is treated as one — real browsers always send it.
     */
    public function isCrawler(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $userAgent = strtolower($userAgent);

        foreach (self::CRAWLER_TOKENS as $token) {
            if (str_contains($userAgent, $token)) {
                return true;
            }
        }

        return false;
    }
}
