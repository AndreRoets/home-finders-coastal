<?php

namespace App\Services\Corex;

use App\Services\Stats\ListingStatsPayload;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the CoreX "website" API.
 *
 * Exposes the read-only endpoints the public site needs (agency, agents,
 * listings) and transparently caches GET responses so we don't hammer CoreX
 * on every page view. All methods fail soft: on a network/auth error they
 * log and return an empty result so the public pages still render.
 *
 * @see https://91.99.130.85:8084/api/v1/website CoreX website API
 */
class CorexClient
{
    /**
     * Page size used when fetching the full listings/agents collections. Kept
     * as constants so cache reads (allPages) and cache busting (forget*) agree
     * on the exact cache key.
     */
    protected const LISTINGS_PER_PAGE = 50;

    protected const SOLD_PER_PAGE = 50;

    protected const AGENTS_PER_PAGE = 100;

    protected const TESTIMONIALS_PER_PAGE = 100;

    protected const ARTICLES_PER_PAGE = 50;

    protected const BRANCHES_PER_PAGE = 100;

    /**
     * Pages requested at once after page 1; bounded so a full listings refresh
     * never opens dozens of simultaneous connections to CoreX.
     */
    protected const CONCURRENT_PAGES = 5;

    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected int $timeout = 10,
        protected int $cacheTtl = 300,
        protected string $statsEndpoint = '/listings/stats',
    ) {}

    /**
     * Health check. Returns true when CoreX answers the ping with a 2xx.
     */
    public function ping(): bool
    {
        try {
            return $this->request()->get('/ping')->successful();
        } catch (ConnectionException $e) {
            return false;
        }
    }

    /**
     * Fetch the agency profile (public contact details, social links, opening
     * hours and branding), unwrapping the JSON:API "data" envelope.
     *
     * @return array<string, mixed>
     */
    public function agency(): array
    {
        return $this->extractData($this->cachedGet('agency', '/agency'));
    }

    /**
     * Fetch every agent, transparently following pagination.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function agents(array $query = []): array
    {
        return $this->allPages('agents', '/agents', $query, self::AGENTS_PER_PAGE);
    }

    /**
     * Fetch a single agent by id.
     *
     * @return array<string, mixed>
     */
    public function agent(int|string $id): array
    {
        $response = $this->cachedGet('agent:'.$id, '/agents/'.$id);

        return $this->extractData($response);
    }

    /**
     * Fetch every syndicated listing, transparently following pagination.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function listings(array $query = []): array
    {
        return $this->allPages('listings', '/listings', $query, self::LISTINGS_PER_PAGE);
    }

    /**
     * Fetch every sold property, transparently following pagination.
     *
     * Since 27 Oct 2026 /listings carries on-market stock only; sold stock is
     * served from its own /sold endpoint with a different shape (sale_price,
     * sold_at, days_on_market; suburb + city only, never a street address).
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function sold(array $query = []): array
    {
        return $this->allPages('sold', '/sold', $query, self::SOLD_PER_PAGE);
    }

    /**
     * Respond to a listing leaving the market (listing.removed): drop the
     * cached /sold collection and re-pull it, so a property that just sold
     * shows on the Sold page straight away rather than after the TTL lapses.
     * Fails soft like every other fetch.
     */
    public function refreshSold(): void
    {
        Cache::forget($this->collectionCacheKey('sold', [], self::SOLD_PER_PAGE));

        $this->sold();
    }

    /**
     * Fetch a single listing by its id or reference.
     *
     * @return array<string, mixed>
     */
    public function listing(int|string $idOrRef): array
    {
        $response = $this->cachedGet('listing:'.$idOrRef, '/listings/'.$idOrRef);

        return $this->extractData($response);
    }

    /**
     * Fetch published testimonials, transparently following pagination.
     * Supports filtering by agent via the `agent_id` query parameter.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function testimonials(array $query = []): array
    {
        return $this->allPages('testimonials', '/testimonials', $query, self::TESTIMONIALS_PER_PAGE);
    }

    /**
     * Fetch a single testimonial by id.
     *
     * @return array<string, mixed>
     */
    public function testimonial(int|string $id): array
    {
        $response = $this->cachedGet('testimonial:'.$id, '/testimonials/'.$id);

        return $this->extractData($response);
    }

    /**
     * Fetch published articles, transparently following pagination.
     * Supports filtering by agent via the `agent_id` query parameter.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function articles(array $query = []): array
    {
        return $this->allPages('articles', '/articles', $query, self::ARTICLES_PER_PAGE);
    }

    /**
     * Fetch a single published article by id.
     *
     * @return array<string, mixed>
     */
    public function article(int|string $id): array
    {
        $response = $this->cachedGet('article:'.$id, '/articles/'.$id);

        return $this->extractData($response);
    }

    /**
     * Fetch every branch (physical office), transparently following pagination.
     * Requires the API key to carry the "branches:read" scope; a 403/401 fails
     * soft to an empty list (the website then simply hides the branches section).
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function branches(array $query = []): array
    {
        return $this->allPages('branches', '/branches', $query, self::BRANCHES_PER_PAGE);
    }

    /**
     * Fetch a single branch by id, including its nested public agents.
     *
     * @return array<string, mixed>
     */
    public function branch(int|string $id): array
    {
        return $this->extractData($this->cachedGet('branch:'.$id, '/branches/'.$id));
    }

    /**
     * Bust the caches affected by a branch change.
     */
    public function forgetBranch(int|string|null $id = null): void
    {
        if ($id !== null && $id !== '') {
            Cache::forget('corex:branch:'.$id);
        }

        Cache::forget($this->collectionCacheKey('branches', [], self::BRANCHES_PER_PAGE));
    }

    /**
     * Fetch all pages of a Laravel-paginated collection and return the merged
     * `data` rows. The whole set is cached under one key.
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    protected function allPages(string $key, string $path, array $query, int $perPage): array
    {
        if ($this->cacheTtl <= 0) {
            return $this->fetchAllPages($path, $query, $perPage)['rows'];
        }

        $cacheKey = $this->collectionCacheKey($key, $query, $perPage);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // Cold cache. Rebuilding a big collection takes seconds, so only one
        // request does it; concurrent visitors wait for that result rather than
        // each repeating the whole fetch and exhausting the PHP worker pool.
        $lock = Cache::lock('lock:'.$cacheKey, 120);

        try {
            $lock->block(25);
        } catch (LockTimeoutException) {
            return Cache::get($cacheKey) ?? $this->fetchAllPages($path, $query, $perPage)['rows'];
        }

        try {
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }

            $result = $this->fetchAllPages($path, $query, $perPage);

            // Never pin a truncated collection for the whole TTL.
            if ($result['complete']) {
                Cache::put($cacheKey, $result['rows'], $this->cacheTtl);
            }

            return $result['rows'];
        } finally {
            $lock->release();
        }
    }

    /**
     * Refresh the shared CoreX collections in place, so visitors keep reading
     * the current copy until the new one lands and never pay for a cold fetch.
     * Run on a schedule more often than the cache TTL. A failed or empty
     * response leaves the existing cache entry untouched.
     *
     * @return int Number of cache entries refreshed.
     */
    public function warm(): int
    {
        if ($this->cacheTtl <= 0) {
            return 0;
        }

        $refreshed = 0;

        $collections = [
            ['listings', '/listings', self::LISTINGS_PER_PAGE],
            ['sold', '/sold', self::SOLD_PER_PAGE],
            ['agents', '/agents', self::AGENTS_PER_PAGE],
            ['testimonials', '/testimonials', self::TESTIMONIALS_PER_PAGE],
            ['articles', '/articles', self::ARTICLES_PER_PAGE],
            ['branches', '/branches', self::BRANCHES_PER_PAGE],
        ];

        foreach ($collections as [$key, $path, $perPage]) {
            $result = $this->fetchAllPages($path, [], $perPage);

            if ($result['complete'] && $result['rows'] !== []) {
                Cache::put($this->collectionCacheKey($key, [], $perPage), $result['rows'], $this->cacheTtl);
                $refreshed++;
            }
        }

        $agency = $this->get('/agency');

        if ($agency !== []) {
            Cache::put('corex:agency', $agency, $this->cacheTtl);
            $refreshed++;
        }

        return $refreshed;
    }

    /**
     * Fetch every page of a Laravel-paginated collection. Page 1 reveals the
     * page count; the remaining pages are then requested concurrently.
     *
     * @param  array<string, mixed>  $query
     * @return array{rows: array<int, array<string, mixed>>, complete: bool}
     */
    protected function fetchAllPages(string $path, array $query, int $perPage): array
    {
        $pageQuery = static fn (int $page): array => array_merge($query, [
            'per_page' => $perPage,
            'page' => $page,
        ]);

        $first = $this->get($path, $pageQuery(1));
        $rows = $first['data'] ?? [];

        if ($rows === []) {
            return ['rows' => [], 'complete' => $first !== []];
        }

        // CoreX exposes the page count under meta.last_page, but fail safe if
        // it ever moves or is omitted: without a known last page we keep paging
        // until a short (final) page arrives, so the full collection is fetched
        // instead of silently stopping at page 1.
        $lastPage = $first['meta']['last_page'] ?? $first['last_page'] ?? null;

        if ($lastPage === null) {
            return $this->fetchRemainingSequentially($path, $pageQuery, $rows, $perPage);
        }

        $lastPage = (int) $lastPage;

        if ($lastPage < 2) {
            return ['rows' => $rows, 'complete' => true];
        }

        $complete = true;

        foreach (array_chunk(range(2, $lastPage), self::CONCURRENT_PAGES) as $pages) {
            $responses = Http::pool(fn (Pool $pool): array => array_map(
                fn (int $page) => $pool->as((string) $page)
                    ->baseUrl($this->baseUrl)
                    ->acceptJson()
                    ->withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->get($path, $pageQuery($page)),
                $pages,
            ));

            foreach ($pages as $page) {
                $response = $responses[(string) $page] ?? null;

                // A failed concurrent page is retried once on the normal
                // (retrying, logging) path before the collection is declared
                // incomplete.
                $data = $response instanceof Response && $response->successful()
                    ? ($response->json('data') ?? [])
                    : ($this->get($path, $pageQuery($page))['data'] ?? []);

                if ($data === []) {
                    $complete = false;

                    continue;
                }

                array_push($rows, ...$data);
            }
        }

        return ['rows' => $rows, 'complete' => $complete];
    }

    /**
     * Page one page at a time until a short (final) page arrives. Used only when
     * CoreX omits the page count.
     *
     * @param  callable(int): array<string, mixed>  $pageQuery
     * @param  array<int, array<string, mixed>>  $rows  Rows of page 1.
     * @return array{rows: array<int, array<string, mixed>>, complete: bool}
     */
    protected function fetchRemainingSequentially(string $path, callable $pageQuery, array $rows, int $perPage): array
    {
        $page = 1;
        $data = $rows;

        while (count($data) >= $perPage) {
            $data = $this->get($path, $pageQuery(++$page))['data'] ?? [];

            if ($data === []) {
                break;
            }

            array_push($rows, ...$data);
        }

        return ['rows' => $rows, 'complete' => true];
    }

    /**
     * Cache key for a paginated collection. Centralised so cache reads and
     * cache busting derive the same key from the same inputs.
     *
     * @param  array<string, mixed>  $query
     */
    protected function collectionCacheKey(string $key, array $query, int $perPage): string
    {
        return 'corex:'.$key.':'.md5(serialize($query)).':'.$perPage;
    }

    /**
     * Bust the caches affected by a listing change so the next page view pulls
     * fresh data from CoreX. Drops the single-resource entry (which may be keyed
     * by id or by reference), the agency-wide listings collection and — for every
     * agent attributed to the listing — that agent's filtered listings collection,
     * which is what the agent profile page reads. Without the last one a relisted
     * or re-attributed property stays invisible on the agent's profile until the
     * TTL lapses, even though it reappears in the main/search listings.
     *
     * @param  array<int, int|string>  $agentIds  Agents attributed to the listing
     *                                            (primary + any co-listing agent).
     */
    public function forgetListing(int|string|null $id = null, ?string $reference = null, array $agentIds = []): void
    {
        foreach ([$id, $reference] as $idOrRef) {
            if ($idOrRef !== null && $idOrRef !== '') {
                Cache::forget('corex:listing:'.$idOrRef);
            }
        }

        Cache::forget($this->collectionCacheKey('listings', [], self::LISTINGS_PER_PAGE));

        foreach ($agentIds as $agentId) {
            $this->forgetAgentListings($agentId);
        }
    }

    /**
     * Bust an agent's filtered listings collection (GET /listings?agent_id={id}),
     * which backs the agent profile page.
     *
     * The profile page caches this list keyed on the agent id, whose PHP type
     * differs by entry point — a string from the "/agents/{id}" route param, an
     * int from the id embedded on CoreX data when resolved via a name slug. Since
     * the cache key is derived from serialize(), int 26 and string "26" hash to
     * different keys, so we forget both variants to guarantee a hit.
     */
    protected function forgetAgentListings(int|string $agentId): void
    {
        if ($agentId === '' || $agentId === null) {
            return;
        }

        foreach ([(int) $agentId, (string) $agentId] as $value) {
            Cache::forget($this->collectionCacheKey('listings', ['agent_id' => $value], self::LISTINGS_PER_PAGE));
        }
    }

    /**
     * Bust the cached agency profile so the next page view pulls fresh public
     * contact details, social links and opening hours.
     */
    public function forgetAgency(): void
    {
        Cache::forget('corex:agency');
    }

    /**
     * Bust the caches affected by an agent change.
     */
    public function forgetAgent(int|string|null $id = null): void
    {
        if ($id !== null && $id !== '') {
            Cache::forget('corex:agent:'.$id);
        }

        Cache::forget($this->collectionCacheKey('agents', [], self::AGENTS_PER_PAGE));
    }

    /**
     * Bust the caches affected by a testimonial change.
     */
    public function forgetTestimonial(int|string|null $id = null): void
    {
        if ($id !== null && $id !== '') {
            Cache::forget('corex:testimonial:'.$id);
        }

        Cache::forget($this->collectionCacheKey('testimonials', [], self::TESTIMONIALS_PER_PAGE));
    }

    /**
     * Bust the caches affected by an article change. Clears the single-article
     * entry, the agency-wide articles collection and — when the change carries
     * an agent id — that agent's filtered articles collection, which is what the
     * agent profile page reads. Without the latter a freshly published article
     * stays invisible on the author's profile until the TTL lapses.
     */
    public function forgetArticle(int|string|null $id = null, int|string|null $agentId = null): void
    {
        if ($id !== null && $id !== '') {
            Cache::forget('corex:article:'.$id);
        }

        Cache::forget($this->collectionCacheKey('articles', [], self::ARTICLES_PER_PAGE));

        if ($agentId !== null && $agentId !== '') {
            Cache::forget($this->collectionCacheKey('articles', ['agent_id' => $agentId], self::ARTICLES_PER_PAGE));
        }
    }

    /**
     * Respond to an article.* webhook: "this agent's articles changed — refresh
     * them". Busts the caches the change touches (single article, agency-wide
     * collection and the agent-scoped collection that backs the agent profile),
     * then eagerly re-pulls GET /articles?agent_id={agentId} so the next profile
     * view is already warm rather than paying a cold CoreX fetch.
     *
     * Idempotent: forget-then-re-pull is safe to run repeatedly, so a redelivered
     * webhook simply re-warms the same cache with the same result. The re-pull
     * fails soft (CorexClient::get), so a transient CoreX hiccup never errors the
     * webhook — it just leaves the cache to fill lazily on the next view.
     */
    public function refreshAgentArticles(int|string|null $agentId, int|string|null $id = null): void
    {
        $this->forgetArticle($id, $agentId);

        if ($agentId !== null && $agentId !== '') {
            $this->articles(['agent_id' => $agentId]);
        }
    }

    /**
     * Submit a website enquiry to CoreX as a lead so it lands in the agency's
     * CRM. POSTs to the website API's leads endpoint with the visitor's details
     * and the property they enquired about.
     *
     * Fails soft: on any network/auth error or non-2xx response it logs and
     * returns false, so the enquiry flow still completes for the visitor (the
     * email to the agent is the primary channel and is sent regardless).
     *
     * @param  array<string, mixed>  $payload
     */
    public function createLead(array $payload): bool
    {
        try {
            $response = $this->request()->post('/leads', $payload);

            if ($response->failed()) {
                Log::warning('CoreX lead submission failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('CoreX lead submission errored', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Report a batch of website engagement statistics (views, impressions,
     * contact clicks, enquiries) for the listings CoreX syndicates to us, so
     * the agency can see how each property is performing on the site from
     * inside their CRM.
     *
     * The payload is built by {@see ListingStatsPayload}
     * and carries a `batch_id` for idempotent redelivery. Returns true only on
     * a 2xx — the caller relies on that to decide whether the counters were
     * accepted, so anything else must fail (and be retried on the next run)
     * rather than be silently swallowed.
     *
     * @param  array<string, mixed>  $payload
     */
    public function pushListingStats(array $payload): bool
    {
        try {
            $response = $this->request()->post($this->statsEndpoint, $payload);

            if ($response->failed()) {
                Log::warning('CoreX listing stats push failed', [
                    'endpoint' => $this->statsEndpoint,
                    'batch_id' => $payload['batch_id'] ?? null,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (ConnectionException|RequestException $e) {
            Log::warning('CoreX listing stats push errored', [
                'endpoint' => $this->statsEndpoint,
                'batch_id' => $payload['batch_id'] ?? null,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Perform a cached GET request, returning the decoded JSON body. On
     * failure an empty array is returned and the error is logged.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function cachedGet(string $key, string $path, array $query = []): array
    {
        if ($this->cacheTtl <= 0) {
            return $this->get($path, $query);
        }

        return Cache::remember(
            'corex:'.$key,
            $this->cacheTtl,
            fn (): array => $this->get($path, $query),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        try {
            $response = $this->request()->get($path, $query);

            if ($response->failed()) {
                Log::warning('CoreX request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json() ?? [];
        } catch (ConnectionException|RequestException $e) {
            Log::warning('CoreX request errored', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Build a pre-configured pending request (base URL, auth, retries).
     */
    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->withToken($this->apiKey)
            ->timeout($this->timeout)
            ->retry(2, 200, throw: false);
    }

    /**
     * Unwrap a JSON:API style envelope. CoreX may return either the payload
     * directly or wrapped under a "data" key — handle both.
     *
     * @param  array<string, mixed>  $response
     * @return array<mixed>
     */
    protected function extractData(array $response): array
    {
        if (array_key_exists('data', $response)) {
            return $response['data'] ?? [];
        }

        return $response;
    }
}
