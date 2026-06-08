<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Transforms a raw CoreX listing resource into the shape the React frontend
 * expects (see resources/js/components/public/listings.ts).
 *
 * Field names below mirror the authoritative CoreX website API resource.
 */
class ListingMapper
{
    /**
     * Map a collection of CoreX listings.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @param  string|null  $forceStatus  Override the badge status (e.g. 'exclusive').
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $listings, ?string $forceStatus = null): array
    {
        return array_values(array_map(
            static fn (array $listing): array => self::map($listing, $forceStatus),
            $listings,
        ));
    }

    /**
     * Map a single CoreX listing to the frontend listing shape.
     *
     * @param  array<string, mixed>  $listing
     * @return array{
     *     id: int|string,
     *     ref: string|null,
     *     title: string,
     *     location: string,
     *     price: string,
     *     beds: int,
     *     baths: int,
     *     area: string,
     *     status: string,
     *     image: string,
     *     url: string|null,
     *     agent: array{id: int|string, name: string, designation: string|null, photo: string}|null,
     *     agents: array<int, array{id: int|string, name: string, designation: string|null, photo: string}>,
     * }
     */
    public static function map(array $listing, ?string $forceStatus = null): array
    {
        $agents = self::cardAgents($listing);

        return [
            'id' => Arr::get($listing, 'id', ''),
            'ref' => Arr::get($listing, 'reference'),
            'title' => (string) (Arr::get($listing, 'title')
                ?? Arr::get($listing, 'headline', 'Untitled listing')),
            'location' => self::location($listing),
            'price' => self::price($listing),
            'beds' => (int) Arr::get($listing, 'beds', 0),
            'baths' => (int) round((float) Arr::get($listing, 'baths', 0)),
            'area' => self::area($listing),
            'status' => $forceStatus ?? self::status($listing),
            'exclusive' => self::isSole($listing),
            'image' => self::image($listing),
            // Title-based, search-friendly path segment for property.show, e.g.
            // "apartment-for-sale-in-margate-kwazulu-natal-12345". The trailing
            // id is what the controller resolves the listing by.
            'slug' => self::slug($listing),
            'url' => Arr::get($listing, 'url') ?? Arr::get($listing, 'permalink'),
            // `agent` (the primary) is kept for backwards compatibility; `agents`
            // carries every attributed agent so a co-listed property shows both.
            'agent' => $agents[0] ?? null,
            'agents' => $agents,
        ];
    }

    /**
     * Build the canonical, title-based URL slug for a listing:
     * "{slugified-title}-{id}", e.g. "apartment-for-sale-in-margate-kwazulu-natal-12345".
     *
     * The trailing id is the lookup key {@see self::idFromSlug()} reads back, so
     * the title can change without breaking the URL. Falls back to the bare id
     * when the listing has no usable title.
     *
     * @param  array<string, mixed>  $listing
     */
    public static function slug(array $listing): string
    {
        $id = (string) Arr::get($listing, 'id', '');

        $title = (string) (Arr::get($listing, 'title')
            ?? Arr::get($listing, 'headline', ''));

        $base = Str::slug($title);

        return $base !== '' ? $base.'-'.$id : $id;
    }

    /**
     * Resolve the CoreX lookup key from a property.show path segment.
     *
     * A canonical slug ends in "-{id}", so we take the trailing number as the
     * id. Anything else (a bare id, or an agency reference such as "RX12345")
     * is returned untouched for backwards-compatible resolution.
     */
    public static function idFromSlug(string $idOrSlug): string
    {
        if (preg_match('/-(\d+)$/', $idOrSlug, $matches) === 1) {
            return $matches[1];
        }

        return $idOrSlug;
    }

    /**
     * Whether the listing is a sole mandate (HFC Exclusive). CoreX expresses
     * this on the `mandate_type` field (value "sole"); we match a "sole"
     * substring case-insensitively and also accept a `mandate` field or a
     * boolean sole flag, so naming/casing variants in the live feed still
     * resolve. Drives the "Exclusive" tag shown on for-sale/to-rent cards.
     *
     * @param  array<string, mixed>  $listing
     */
    public static function isSole(array $listing): bool
    {
        foreach (['mandate_type', 'mandate'] as $key) {
            $value = $listing[$key] ?? null;

            if (is_string($value) && str_contains(strtolower($value), 'sole')) {
                return true;
            }
        }

        foreach (['sole_mandate', 'is_sole', 'sole'] as $key) {
            if (filter_var($listing[$key] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the agents attributed to a listing, primary first, deduped by id.
     *
     * CoreX now returns an `agents` array on every listing — the primary first
     * (flagged `is_primary: true`) and any co-listing agent after. We prefer it
     * and sort `is_primary` to the front so the primary always leads regardless
     * of array order, then dedupe by id. Older payloads carry only the singular
     * `agent` (the primary); we fall back to it so single-agent listings keep
     * working. Entries without an id are dropped.
     *
     * @param  array<string, mixed>  $listing
     * @return array<int, array<string, mixed>>
     */
    public static function extractAgents(array $listing): array
    {
        $agents = Arr::get($listing, 'agents');

        $candidates = is_array($agents) && $agents !== []
            ? array_values(array_filter($agents, 'is_array'))
            : array_values(array_filter([Arr::get($listing, 'agent')], 'is_array'));

        $resolved = [];
        $seen = [];

        foreach ($candidates as $agent) {
            if (! Arr::has($agent, 'id')) {
                continue;
            }

            $id = (string) Arr::get($agent, 'id');

            if (isset($seen[$id])) {
                continue;
            }

            $seen[$id] = true;
            $resolved[] = $agent;
        }

        // Float the primary (is_primary: true) to the front. usort is stable on
        // PHP 8, so co-agents keep their relative order.
        usort(
            $resolved,
            static fn (array $a, array $b): int => (int) Arr::get($b, 'is_primary', false) <=> (int) Arr::get($a, 'is_primary', false),
        );

        return $resolved;
    }

    /**
     * The compact agents (name + photo, linked to the agent detail page) shown
     * on a listing card. Empty when the listing has no attributed agent.
     *
     * @param  array<string, mixed>  $listing
     * @return array<int, array{id: int|string, name: string, designation: string|null, photo: string}>
     */
    protected static function cardAgents(array $listing): array
    {
        return array_map(static function (array $agent): array {
            $mapped = AgentMapper::map($agent);

            return [
                'id' => $mapped['id'],
                'name' => $mapped['name'],
                'designation' => $mapped['designation'],
                'photo' => $mapped['photo'],
            ];
        }, self::extractAgents($listing));
    }

    /**
     * Map a single listing to the richer shape used by the detail page.
     *
     * @param  array<string, mixed>  $listing
     * @return array<string, mixed>
     */
    public static function detail(array $listing): array
    {
        $agents = array_map(
            static fn (array $agent): array => AgentMapper::map($agent),
            self::extractAgents($listing),
        );

        return array_merge(self::map($listing), [
            'headline' => Arr::get($listing, 'headline'),
            'description' => Arr::get($listing, 'description'),
            'propertyType' => Arr::get($listing, 'property_type'),
            'category' => Arr::get($listing, 'category'),
            'listingType' => Arr::get($listing, 'listing_type'),
            'garages' => (int) Arr::get($listing, 'garages', 0),
            'erfSize' => is_numeric(Arr::get($listing, 'erf_size_m2'))
                ? number_format((float) $listing['erf_size_m2'], 0, '.', ' ').' m²'
                : null,
            'petFriendly' => (bool) Arr::get($listing, 'pet_friendly', false),
            'address' => self::address($listing),
            'features' => array_values(array_filter(
                (array) Arr::get($listing, 'features', []),
                'is_string',
            )),
            'images' => array_values(array_map(
                self::normalizeImageUrl(...),
                array_filter(
                    (array) Arr::get($listing, 'images', []),
                    static fn ($i): bool => is_string($i) && $i !== '',
                ),
            )),
            'costs' => [
                'ratesTaxes' => Arr::get($listing, 'costs.rates_taxes'),
                'levy' => Arr::get($listing, 'costs.levy'),
                'specialLevy' => Arr::get($listing, 'costs.special_levy'),
            ],
            // Full agents (with phone/email) for the detail sidebar; `agent`
            // stays as the primary for backwards compatibility.
            'agent' => $agents[0] ?? null,
            'agents' => $agents,
        ]);
    }

    /**
     * Build a single-line street address from the available parts.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function address(array $listing): ?string
    {
        $explicit = Arr::get($listing, 'address');

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $street = trim((string) Arr::get($listing, 'street_number', '').' '.(string) Arr::get($listing, 'street_name', ''));

        $parts = array_filter([
            Arr::get($listing, 'complex_name'),
            $street !== '' ? $street : null,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    /**
     * Build a human-readable location from the most specific parts available.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function location(array $listing): string
    {
        $parts = array_filter([
            Arr::get($listing, 'suburb'),
            Arr::get($listing, 'town') ?? Arr::get($listing, 'city'),
        ]);

        return $parts !== [] ? implode(', ', $parts) : (string) Arr::get($listing, 'province', '');
    }

    /**
     * Format the price for display. CoreX provides a ready-formatted
     * `price_display`; rentals carry their amount on a nested `rental` object.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function price(array $listing): string
    {
        if (Arr::get($listing, 'price_on_application')) {
            return 'Price on application';
        }

        $rental = Arr::get($listing, 'rental');

        if (is_array($rental) && is_numeric(Arr::get($rental, 'rental_amount'))) {
            $amount = 'R '.number_format((float) $rental['rental_amount'], 0, '.', ' ');

            return $amount.self::rentalSuffix((string) Arr::get($rental, 'rental_price_type', 'per_month'));
        }

        $display = Arr::get($listing, 'price_display');

        if (is_string($display) && $display !== '') {
            return $display;
        }

        $price = Arr::get($listing, 'price');

        return is_numeric($price)
            ? 'R '.number_format((float) $price, 0, '.', ' ')
            : 'Price on application';
    }

    protected static function rentalSuffix(string $type): string
    {
        return match ($type) {
            'per_day' => ' / day',
            'per_week' => ' / week',
            'per_year' => ' / year',
            default => ' / month',
        };
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected static function area(array $listing): string
    {
        $size = Arr::get($listing, 'size_m2') ?? Arr::get($listing, 'erf_size_m2');

        return is_numeric($size) ? number_format((float) $size, 0, '.', ' ').' m²' : '—';
    }

    /**
     * @param  array<string, mixed>  $listing
     */
    protected static function image(array $listing): string
    {
        $image = Arr::get($listing, 'images.0');

        return is_string($image) && $image !== ''
            ? self::normalizeImageUrl($image)
            : 'https://placehold.co/1200x900?text=No+Image';
    }

    /**
     * Defend against a malformed CoreX media URL that carries a doubled
     * `/storage/storage/` path segment, which 403s. Collapse it back to a
     * single `/storage/` so the image resolves.
     */
    protected static function normalizeImageUrl(string $url): string
    {
        return str_replace('/storage/storage/', '/storage/', $url);
    }

    /**
     * Map CoreX status + listing_type to a frontend badge key.
     *
     * @param  array<string, mixed>  $listing
     */
    protected static function status(array $listing): string
    {
        if (str_contains(strtolower((string) Arr::get($listing, 'status')), 'sold')) {
            return 'sold';
        }

        $type = strtolower((string) Arr::get($listing, 'listing_type', ''));

        return (str_contains($type, 'rent') || str_contains($type, 'let')) ? 'to-rent' : 'for-sale';
    }
}
