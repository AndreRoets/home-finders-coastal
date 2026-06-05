<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Services\Corex\ListingSearch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListingController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

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
        return Inertia::render('home', [
            'featured' => $this->featured(),
            'filters' => ListingSearch::facets($this->filter(fn (array $l): bool => $this->isAvailable($l))),
        ]);
    }

    /**
     * A handful of featured (sole-mandate) listings shared by the home variants.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function featured(): array
    {
        $exclusive = $this->filter(fn (array $l): bool => $this->isExclusive($l));

        return array_slice(ListingMapper::collection($exclusive, 'exclusive'), 0, 6);
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
        ]);
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
        ]);
    }

    /**
     * Fetch all syndicated listings and keep those matching the predicate.
     * CoreX only paginates the listings endpoint, so the bucket filtering
     * happens here rather than via (unsupported) query parameters.
     *
     * @param  callable(array<string, mixed>): bool  $predicate
     * @return array<int, array<string, mixed>>
     */
    protected function filter(callable $predicate): array
    {
        return array_values(array_filter($this->corex->listings(), $predicate));
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
        return $this->isAvailable($listing)
            && strtolower((string) ($listing['mandate_type'] ?? '')) === 'sole';
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
