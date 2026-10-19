<?php

namespace App\Services\Branches;

use App\Services\Corex\AgentMapper;
use Illuminate\Support\Arr;

/**
 * Transforms a raw CoreX branch resource into the shape the React Offices pages
 * expect.
 *
 * Field names mirror the CoreX "Agency Public API" branch resource. Optional
 * fields are omitted by CoreX when blank; a blank branch field means "use the
 * agency default", so logo/phone/email/address fall back to the values from
 * GET /agency (passed in as $defaults — the mapped agency array). Fields with no
 * agency equivalent (tagline, phone label, secondary phone, PPRA number) simply
 * collapse to null and are hidden by the frontend.
 */
class BranchMapper
{
    /**
     * @param  array<int, array<string, mixed>>  $branches
     * @param  array<string, mixed>  $defaults  The mapped agency (AgencyMapper::map output).
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $branches, array $defaults = []): array
    {
        return array_values(array_map(
            static fn (array $branch): array => self::map($branch, $defaults),
            $branches,
        ));
    }

    /**
     * @param  array<string, mixed>  $branch
     * @param  array<string, mixed>  $defaults  The mapped agency (AgencyMapper::map output).
     * @return array<string, mixed>
     */
    public static function map(array $branch, array $defaults = []): array
    {
        $phone = self::string($branch, 'phone') ?? Arr::get($defaults, 'contact.phone');
        $phoneSecondary = self::string($branch, 'phone_secondary');
        $address = self::string($branch, 'address') ?? Arr::get($defaults, 'contact.address');

        return [
            'id' => Arr::get($branch, 'id'),
            'tradingName' => (string) (self::string($branch, 'trading_name') ?? 'Office'),
            'tagline' => self::string($branch, 'tagline'),
            'address' => $address,
            'town' => self::string($branch, 'town')
                ?? self::string($branch, 'city')
                ?? self::string($branch, 'suburb')
                ?? self::townFromAddress($address),
            'phone' => $phone,
            'phoneHref' => self::href($phone),
            'phoneLabel' => self::string($branch, 'phone_label'),
            'phoneSecondary' => $phoneSecondary,
            'phoneSecondaryHref' => self::href($phoneSecondary),
            'phoneSecondaryLabel' => self::string($branch, 'phone_secondary_label'),
            'email' => self::string($branch, 'email') ?? Arr::get($defaults, 'contact.email'),
            'ppraNumber' => self::string($branch, 'ppra_number'),
            'logo' => self::string($branch, 'logo_url') ?? Arr::get($defaults, 'logo'),
            'agentCount' => (int) Arr::get($branch, 'agent_count', 0),
            'listingCount' => (int) Arr::get($branch, 'listing_count', 0),
            'agents' => AgentMapper::collection(
                array_values(array_filter((array) Arr::get($branch, 'agents', []), 'is_array')),
            ),
        ];
    }

    /**
     * Best-effort town from a free-text address: the last comma-separated part
     * that isn't a postal code, e.g. "12 Marina Drive, Margate, 4275" → "Margate".
     */
    protected static function townFromAddress(?string $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $parts = array_reverse(array_map('trim', explode(',', $address)));

        foreach (array_slice($parts, 0, count($parts) > 1 ? -1 : 1) as $part) {
            if ($part !== '' && preg_match('/^\d+$/', $part) !== 1) {
                return $part;
            }
        }

        return null;
    }

    /**
     * Whitespace-stripped phone for a `tel:` href, or null when there is no phone.
     */
    protected static function href(?string $phone): ?string
    {
        return $phone !== null ? (string) preg_replace('/\s+/', '', $phone) : null;
    }

    /**
     * Pull a trimmed string from $source[$key], collapsing missing/blank to null.
     *
     * @param  array<string, mixed>  $source
     */
    protected static function string(array $source, string $key): ?string
    {
        $value = Arr::get($source, $key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
