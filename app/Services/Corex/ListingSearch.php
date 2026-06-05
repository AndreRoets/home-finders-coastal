<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Derives search facets from a set of raw CoreX listings and filters that set
 * by the search parameters submitted from the public search bar.
 *
 * Facets are computed from whatever listings are actually available, so the
 * search bar only ever offers suburbs / property types / price ranges that
 * exist in the live data.
 */
class ListingSearch
{
    /**
     * Build the available filter options from a set of raw listings.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array{
     *     suburbs: list<string>,
     *     propertyTypes: list<string>,
     *     maxBeds: int,
     *     maxBaths: int,
     *     price: array{sale: array{min: int, max: int}, rent: array{min: int, max: int}}
     * }
     */
    public static function facets(array $listings): array
    {
        $suburbs = [];
        $types = [];
        $maxBeds = 0;
        $maxBaths = 0;
        $saleMin = $saleMax = $rentMin = $rentMax = null;

        foreach ($listings as $listing) {
            $suburb = trim((string) Arr::get($listing, 'suburb', ''));

            if ($suburb !== '') {
                $suburbs[$suburb] = true;
            }

            $type = trim((string) Arr::get($listing, 'property_type', ''));

            if ($type !== '') {
                $types[$type] = true;
            }

            $maxBeds = max($maxBeds, (int) Arr::get($listing, 'beds', 0));
            $maxBaths = max($maxBaths, (int) round((float) Arr::get($listing, 'baths', 0)));

            $price = self::priceOf($listing);

            if ($price === null) {
                continue;
            }

            if (self::isRental($listing)) {
                $rentMin = $rentMin === null ? $price : min($rentMin, $price);
                $rentMax = $rentMax === null ? $price : max($rentMax, $price);
            } else {
                $saleMin = $saleMin === null ? $price : min($saleMin, $price);
                $saleMax = $saleMax === null ? $price : max($saleMax, $price);
            }
        }

        ksort($suburbs, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($types, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'suburbs' => array_keys($suburbs),
            'propertyTypes' => array_keys($types),
            'maxBeds' => $maxBeds,
            'maxBaths' => $maxBaths,
            'price' => [
                'sale' => ['min' => $saleMin ?? 0, 'max' => $saleMax ?? 0],
                'rent' => ['min' => $rentMin ?? 0, 'max' => $rentMax ?? 0],
            ],
        ];
    }

    /**
     * Filter a set of raw listings by the submitted search parameters. Empty /
     * absent parameters are ignored so a blank search returns everything.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @param  array<string, mixed>  $params
     * @return array<int, array<string, mixed>>
     */
    public static function apply(array $listings, array $params): array
    {
        $q = strtolower(trim((string) ($params['q'] ?? '')));
        $suburb = trim((string) ($params['suburb'] ?? ''));
        $type = trim((string) ($params['type'] ?? ''));
        $minBeds = (int) ($params['beds'] ?? 0);
        $minBaths = (int) ($params['baths'] ?? 0);
        $minPrice = is_numeric($params['min_price'] ?? null) ? (int) $params['min_price'] : null;
        $maxPrice = is_numeric($params['max_price'] ?? null) ? (int) $params['max_price'] : null;

        $matches = static function (array $listing) use ($q, $suburb, $type, $minBeds, $minBaths, $minPrice, $maxPrice): bool {
            if ($suburb !== '' && strcasecmp((string) Arr::get($listing, 'suburb', ''), $suburb) !== 0) {
                return false;
            }

            if ($type !== '' && strcasecmp((string) Arr::get($listing, 'property_type', ''), $type) !== 0) {
                return false;
            }

            if ($minBeds > 0 && (int) Arr::get($listing, 'beds', 0) < $minBeds) {
                return false;
            }

            if ($minBaths > 0 && (int) round((float) Arr::get($listing, 'baths', 0)) < $minBaths) {
                return false;
            }

            if ($minPrice !== null || $maxPrice !== null) {
                $price = self::priceOf($listing);

                if ($price === null
                    || ($minPrice !== null && $price < $minPrice)
                    || ($maxPrice !== null && $price > $maxPrice)) {
                    return false;
                }
            }

            if ($q !== '' && ! str_contains(self::haystack($listing), $q)) {
                return false;
            }

            return true;
        };

        return array_values(array_filter($listings, $matches));
    }

    /**
     * The lower-cased text a free-text query is matched against.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function haystack(array $listing): string
    {
        return strtolower(implode(' ', array_filter([
            Arr::get($listing, 'title'),
            Arr::get($listing, 'headline'),
            Arr::get($listing, 'suburb'),
            Arr::get($listing, 'town'),
            Arr::get($listing, 'city'),
            Arr::get($listing, 'property_type'),
        ])));
    }

    /**
     * The comparable price: rentals use their monthly amount, sales their price.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function priceOf(array $listing): ?int
    {
        $rental = Arr::get($listing, 'rental');

        if (is_array($rental) && is_numeric(Arr::get($rental, 'rental_amount'))) {
            return (int) $rental['rental_amount'];
        }

        $price = Arr::get($listing, 'price');

        return is_numeric($price) ? (int) $price : null;
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected static function isRental(array $listing): bool
    {
        $type = strtolower((string) Arr::get($listing, 'listing_type', ''));

        return str_contains($type, 'rent') || str_contains($type, 'let');
    }
}
