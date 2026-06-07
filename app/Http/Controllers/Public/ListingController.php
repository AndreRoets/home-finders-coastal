<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchContext;
use App\Services\Corex\AgentMapper;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Services\Corex\ListingSearch;
use App\Services\Corex\TestimonialMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    public function __construct(
        protected CorexClient $corex,
        protected BranchContext $branches,
    ) {}

    /**
     * Single property detail page.
     */
    public function show(string $idOrRef): Response
    {
        $listing = $this->corex->listing($idOrRef);

        if ($listing === []) {
            throw new NotFoundHttpException('Listing not found.');
        }

        return Inertia::render('property-detail', [
            'property' => ListingMapper::detail($listing),
        ]);
    }

    /**
     * Home page with a handful of featured (sole-mandate) listings.
     */
    public function home(): Response
    {
        return Inertia::render('home', $this->homeData());
    }

    /**
     * Shared props for the home page.
     *
     * @return array<string, mixed>
     */
    protected function homeData(): array
    {
        return [
            'recent' => $this->recent(),
            'filters' => ListingSearch::facets($this->filter(fn (array $l): bool => $this->isAvailable($l))),
            'agents' => AgentMapper::collection($this->agents()),
            'testimonials' => TestimonialMapper::collection(
                array_slice($this->corex->testimonials(), 0, 12),
            ),
        ];
    }

    /**
     * The agency's agents, falling back to the agents embedded on listings when
     * the standalone endpoint is empty (mirrors AgentController).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function agents(): array
    {
        $agents = $this->corex->agents();

        if ($agents !== []) {
            return $agents;
        }

        return Collection::make($this->corex->listings())
            ->map(fn (array $listing): mixed => Arr::get($listing, 'agent'))
            ->filter(fn (mixed $agent): bool => is_array($agent) && Arr::has($agent, 'id'))
            ->unique(fn (array $agent): mixed => $agent['id'])
            ->values()
            ->all();
    }

    /**
     * The six most recently listed available properties, shown on the home page.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function recent(): array
    {
        $available = $this->filter(fn (array $l): bool => $this->isAvailable($l));

        usort($available, fn (array $a, array $b): int => $this->listedAt($b) <=> $this->listedAt($a));

        return ListingMapper::collection(array_slice($available, 0, 6));
    }

    /**
     * A sortable "recency" value for a listing. Prefers a listing date when
     * CoreX provides one, otherwise falls back to the (incrementing) id.
     *
     * @param  array<string, mixed>  $listing
     */
    protected function listedAt(array $listing): int
    {
        foreach (['listed_at', 'published_at', 'created_at', 'date_listed', 'updated_at'] as $key) {
            $value = $listing[$key] ?? null;

            if (is_int($value)) {
                return $value;
            }

            if (is_string($value) && $value !== '' && ($timestamp = strtotime($value)) !== false) {
                return $timestamp;
            }
        }

        return (int) ($listing['id'] ?? 0);
    }

    /**
     * Properties for sale.
     */
    public function forSale(Request $request): Response
    {
        $sales = $this->filter(fn (array $l): bool => $this->isActiveSale($l));

        return Inertia::render('for-sale', [
            'listings' => ListingMapper::collection(ListingSearch::apply($sales, $request->all())),
            'filters' => ListingSearch::facets($sales),
            'search' => $this->searchValues($request),
            ...$this->branchProps(),
        ]);
    }

    /**
     * Properties to rent.
     */
    public function toRent(Request $request): Response
    {
        $rentals = $this->filter(fn (array $l): bool => $this->isActiveRental($l));

        return Inertia::render('to-rent', [
            'listings' => ListingMapper::collection(ListingSearch::apply($rentals, $request->all())),
            'filters' => ListingSearch::facets($rentals),
            'search' => $this->searchValues($request),
            ...$this->branchProps(),
        ]);
    }

    /**
     * The Agency/branch filter props shared by the listing index pages: the
     * branch options ("All" is added on the frontend) and the active branch id.
     * Both empty/null when the branches feature is off.
     *
     * @return array{branches: array<int, array{id: int|string, name: string}>, activeBranch: int|string|null}
     */
    protected function branchProps(): array
    {
        return [
            'branches' => $this->branches->summaries(),
            'activeBranch' => $this->branches->activeId(),
        ];
    }

    /**
     * The current search-bar values, echoed back so the bar can pre-fill.
     *
     * @return array<string, mixed>
     */
    protected function searchValues(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'suburb' => (string) $request->query('suburb', ''),
            'type' => (string) $request->query('type', ''),
            'beds' => (int) $request->query('beds', 0),
            'baths' => (int) $request->query('baths', 0),
            'min_price' => is_numeric($request->query('min_price')) ? (int) $request->query('min_price') : null,
            'max_price' => is_numeric($request->query('max_price')) ? (int) $request->query('max_price') : null,
        ];
    }

    /**
     * HFC exclusive (sole-mandate) listings.
     */
    public function exclusive(): Response
    {
        return Inertia::render('hfc-exclusive', [
            'listings' => ListingMapper::collection(
                $this->filter(fn (array $l): bool => $this->isExclusive($l)),
                'exclusive',
            ),
            ...$this->branchProps(),
        ]);
    }

    /**
     * Recently sold properties.
     */
    public function sold(): Response
    {
        return Inertia::render('sold', [
            'listings' => ListingMapper::collection(
                $this->filter(fn (array $l): bool => str_contains($this->status($l), 'sold')),
            ),
            ...$this->branchProps(),
        ]);
    }

    /**
     * Fetch all syndicated listings and keep those matching the predicate.
     * CoreX only paginates the listings endpoint, so the bucket filtering
     * happens here rather than via (unsupported) query parameters.
     *
     * When a branch is selected (?branch_id={id}) and the branches feature is on,
     * the request is scoped to that branch so every bucket — recent, for-sale,
     * rentals, exclusives, sold and the search facets — reflects it.
     *
     * @param  callable(array<string, mixed>): bool  $predicate
     * @return array<int, array<string, mixed>>
     */
    protected function filter(callable $predicate): array
    {
        return array_values(array_filter($this->corex->listings($this->branches->query()), $predicate));
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected function isActiveSale(array $listing): bool
    {
        return $this->isAvailable($listing) && ! $this->isRental($listing);
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected function isActiveRental(array $listing): bool
    {
        return $this->isAvailable($listing) && $this->isRental($listing);
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected function isExclusive(array $listing): bool
    {
        return $this->isAvailable($listing) && $this->isSole($listing);
    }

    /**
     * A sole-mandate (HFC Exclusive) listing. Delegates to {@see ListingMapper::isSole()}
     * so the bucket filter and the card's "Exclusive" tag share one definition.
     *
     * @param  array<string, mixed>  $listing
     */
    protected function isSole(array $listing): bool
    {
        return ListingMapper::isSole($listing);
    }

    /**
     * Available = on the market, i.e. not sold, withdrawn or still a draft.
     * (CoreX uses status values such as "for_sale"/"to_let" for live listings.)
     *
     * @param  array<string, mixed>  $listing
     */
    protected function isAvailable(array $listing): bool
    {
        $status = $this->status($listing);

        return ! str_contains($status, 'sold')
            && ! str_contains($status, 'withdrawn')
            && ! str_contains($status, 'draft');
    }

    /**
     * Rentals are identified by listing_type (sale vs rental/to_let).
     *
     * @param  array<string, mixed>  $listing
     */
    protected function isRental(array $listing): bool
    {
        $type = strtolower((string) ($listing['listing_type'] ?? ''));

        return str_contains($type, 'rent') || str_contains($type, 'let');
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected function status(array $listing): string
    {
        return strtolower((string) ($listing['status'] ?? ''));
    }
}
