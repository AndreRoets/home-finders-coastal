<?php

namespace App\Services\Corex;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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

    protected const AGENTS_PER_PAGE = 100;

    protected const TESTIMONIALS_PER_PAGE = 100;

    protected const ARTICLES_PER_PAGE = 50;

    protected const BRANCHES_PER_PAGE = 100;

    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected int $timeout = 10,
        protected int $cacheTtl = 300,
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
        $cacheKey = $this->collectionCacheKey($key, $query, $perPage);

        $fetch = function () use ($path, $query, $perPage): array {
            $rows = [];
            $page = 1;

            do {
                $response = $this->get($path, array_merge($query, [
                    'per_page' => $perPage,
                    'page' => $page,
                ]));

                $data = $response['data'] ?? [];

                if ($data === []) {
                    break;
                }

                array_push($rows, ...$data);

                // CoreX exposes the page count under meta.last_page, but fail
                // safe if it ever moves or is omitted: without a known last page
                // we keep paging until a short (final) page arrives, so the full
                // collection is fetched instead of silently stopping at page 1.
                $lastPage = $response['meta']['last_page'] ?? $response['last_page'] ?? null;

                if ($lastPage !== null) {
                    if (++$page > (int) $lastPage) {
                        break;
                    }
                } else {
                    if (count($data) < $perPage) {
                        break;
                    }

                    $page++;
                }
            } while (true);

            return $rows;
        };

        if ($this->cacheTtl <= 0) {
            return $fetch();
        }

        return Cache::remember($cacheKey, $this->cacheTtl, $fetch);
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
     * fresh data from CoreX. Both the single-resource entry (which may be keyed
     * by id or by reference) and the listings collection are dropped.
     */
    public function forgetListing(int|string|null $id = null, ?string $reference = null): void
    {
        foreach ([$id, $reference] as $idOrRef) {
            if ($idOrRef !== null && $idOrRef !== '') {
                Cache::forget('corex:listing:'.$idOrRef);
            }
        }

        Cache::forget($this->collectionCacheKey('listings', [], self::LISTINGS_PER_PAGE));
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
