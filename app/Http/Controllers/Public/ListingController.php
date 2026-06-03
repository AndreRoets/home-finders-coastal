<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
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
        ]);
    }

    /**
     * Home page — Luxe Editorial design variant.
     */
    public function homeLuxe(): Response
    {
        return Inertia::render('home-luxe', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Home page — Vibrant Modern design variant.
     */
    public function homeVibrant(): Response
    {
        return Inertia::render('home-vibrant', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Home page — Minimal architectural design variant.
     */
    public function homeMinimal(): Response
    {
        return Inertia::render('home-minimal', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Home page — Portal design variant (industry best-practice layout).
     */
    public function homePortal(): Response
    {
        return Inertia::render('home-portal', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Home page — Motion design variant (scroll-driven animations).
     */
    public function homeMotion(): Response
    {
        return Inertia::render('home-motion', [
            'featured' => $this->featured(),
        ]);
    }

    /**
     * Home page — Coastal/beach themed design variant.
     */
    public function homeCoastal(): Response
    {
        return Inertia::render('home-coastal', [
            'featured' => $this->featured(),
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
    public function forSale(): Response
    {
        return Inertia::render('for-sale', [
            'listings' => ListingMapper::collection(
                $this->filter(fn (array $l): bool => $this->isActiveSale($l)),
            ),
        ]);
    }

    /**
     * Properties to rent.
     */
    public function toRent(): Response
    {
        return Inertia::render('to-rent', [
            'listings' => ListingMapper::collection(
                $this->filter(fn (array $l): bool => $this->isActiveRental($l)),
            ),
        ]);
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
