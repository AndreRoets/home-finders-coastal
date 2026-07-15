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
     *     suggestions: list<string>,
     *     maxBeds: int,
     *     maxBaths: int,
     *     price: array{sale: array{min: int, max: int}, rent: array{min: int, max: int}}
     * }
     */
    public static function facets(array $listings): array
    {
        $suburbs = [];
        $types = [];
        $suggestions = [];
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

            // Place names a keyword search can autocomplete against. Keyed by a
            // lower-cased form so "Sea Point" / "sea point" collapse to one
            // entry, while the first-seen original casing is what we surface.
            foreach (['suburb', 'town', 'city'] as $field) {
                $place = trim((string) Arr::get($listing, $field, ''));

                if ($place !== '' && ! isset($suggestions[mb_strtolower($place)])) {
                    $suggestions[mb_strtolower($place)] = $place;
                }
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
        ksort($suggestions, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'suburbs' => array_keys($suburbs),
            'propertyTypes' => array_keys($types),
            'suggestions' => array_values($suggestions),
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
        $suburbs = self::toList($params['suburb'] ?? null);
        $types = self::toList($params['type'] ?? null);
        $minBeds = (int) ($params['beds'] ?? 0);
        $minBaths = (int) ($params['baths'] ?? 0);
        $minPrice = is_numeric($params['min_price'] ?? null) ? (int) $params['min_price'] : null;
        $maxPrice = is_numeric($params['max_price'] ?? null) ? (int) $params['max_price'] : null;

        $matches = static function (array $listing) use ($q, $suburbs, $types, $minBeds, $minBaths, $minPrice, $maxPrice): bool {
            if ($suburbs !== [] && ! self::containsCi($suburbs, (string) Arr::get($listing, 'suburb', ''))) {
                return false;
            }

            if ($types !== [] && ! self::containsCi($types, (string) Arr::get($listing, 'property_type', ''))) {
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
     * Normalise a search parameter that may arrive as a single value or a list
     * (the search bar sends multiple suburbs / types as an array) into a clean
     * list of non-empty trimmed strings.
     *
     * @return list<string>
     */
    protected static function toList(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(
            array_map(static fn (mixed $item): string => trim((string) $item), $values),
            static fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * Whether $needle case-insensitively matches any entry in $haystack.
     *
     * @param  list<string>  $haystack
     */
    protected static function containsCi(array $haystack, string $needle): bool
    {
        foreach ($haystack as $candidate) {
            if (strcasecmp($candidate, $needle) === 0) {
                return true;
            }
        }

        return false;
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
