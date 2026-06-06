<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

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
     * }
     */
    public static function map(array $listing, ?string $forceStatus = null): array
    {
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
            'image' => self::image($listing),
            'url' => Arr::get($listing, 'url') ?? Arr::get($listing, 'permalink'),
            'agent' => self::cardAgent($listing),
        ];
    }

    /**
     * The compact agent shown on a listing card (name + photo, linked to the
     * agent detail page). Null when the listing has no attributed agent.
     *
     * @param  array<string, mixed>  $listing
     * @return array{id: int|string, name: string, designation: string|null, photo: string}|null
     */
    protected static function cardAgent(array $listing): ?array
    {
        $agent = Arr::get($listing, 'agent');

        if (! is_array($agent) || ! Arr::has($agent, 'id')) {
            return null;
        }

        $mapped = AgentMapper::map($agent);

        return [
            'id' => $mapped['id'],
            'name' => $mapped['name'],
            'designation' => $mapped['designation'],
            'photo' => $mapped['photo'],
        ];
    }

    /**
     * Map a single listing to the richer shape used by the detail page.
     *
     * @param  array<string, mixed>  $listing
     * @return array<string, mixed>
     */
    public static function detail(array $listing): array
    {
        $agent = Arr::get($listing, 'agent');

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
            'images' => array_values(array_filter(
                (array) Arr::get($listing, 'images', []),
                static fn ($i): bool => is_string($i) && $i !== '',
            )),
            'costs' => [
                'ratesTaxes' => Arr::get($listing, 'costs.rates_taxes'),
                'levy' => Arr::get($listing, 'costs.levy'),
                'specialLevy' => Arr::get($listing, 'costs.special_levy'),
            ],
            'agent' => is_array($agent) ? AgentMapper::map($agent) : null,
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
            ? $image
            : 'https://placehold.co/1200x900?text=No+Image';
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
