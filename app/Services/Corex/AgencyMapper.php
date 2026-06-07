<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Transforms the raw CoreX agency resource into the shape the public site's
 * shared layout expects (footer contact block, social icons, opening hours and
 * branding colours).
 *
 * The golden rule of this mapper: a key that CoreX does not set must not appear
 * in the output. Every section collapses to null/[] when empty so the frontend
 * can render each block only when there is something to show — no empty labels,
 * no blank social icons, no orphaned "Opening hours" heading.
 */
class AgencyMapper
{
    /**
     * Map a raw CoreX agency resource (the inner `data` object) to the frontend
     * shape. Returns null when CoreX returns nothing (e.g. a fetch error), so
     * the layout simply renders no dynamic contact details.
     *
     * @param  array<string, mixed>  $agency
     * @return array{
     *     name: string|null,
     *     tradingName: string|null,
     *     logo: string|null,
     *     branding: array<string, string>|null,
     *     contact: array{email?: string, phone?: string, phoneHref?: string, address?: string}|null,
     *     socials: array<string, string>,
     *     openHours: array<int, array{days: string, hours: string}>,
     *     show: array{agents: bool, listings: bool, branches: bool},
     * }|null
     */
    public static function map(array $agency): ?array
    {
        // CoreX wraps the resource under "data"; the demo client returns the
        // inner object directly. Accept either.
        if (array_key_exists('data', $agency) && is_array($agency['data'])) {
            $agency = $agency['data'];
        }

        if ($agency === []) {
            return null;
        }

        return [
            'name' => self::string($agency, 'name'),
            'tradingName' => self::string($agency, 'trading_name'),
            'logo' => self::string($agency, 'logo_url'),
            'branding' => self::branding($agency),
            'contact' => self::contact($agency),
            'socials' => self::socials($agency),
            'openHours' => self::openHours($agency),
            'show' => self::show($agency),
        ];
    }

    /**
     * Section feature toggles (`data.show`). Each flag defaults to false when
     * CoreX omits it, so a section is only ever rendered when explicitly enabled.
     *
     * @param  array<string, mixed>  $agency
     * @return array{agents: bool, listings: bool, branches: bool}
     */
    protected static function show(array $agency): array
    {
        $show = Arr::get($agency, 'show');
        $show = is_array($show) ? $show : [];

        return [
            'agents' => (bool) Arr::get($show, 'agents', false),
            'listings' => (bool) Arr::get($show, 'listings', false),
            'branches' => (bool) Arr::get($show, 'branches', false),
        ];
    }

    /**
     * The public contact block. Each of email/phone/address is optional and is
     * omitted entirely when absent. The phone carries a whitespace-stripped
     * `phoneHref` for the tel: link alongside the formatted display value.
     *
     * @param  array<string, mixed>  $agency
     * @return array{email?: string, phone?: string, phoneHref?: string, address?: string}|null
     */
    protected static function contact(array $agency): ?array
    {
        $contact = Arr::get($agency, 'contact');

        if (! is_array($contact)) {
            return null;
        }

        $result = [];

        if (($email = self::string($contact, 'email')) !== null) {
            $result['email'] = $email;
        }

        if (($phone = self::string($contact, 'phone')) !== null) {
            $result['phone'] = $phone;
            $result['phoneHref'] = (string) preg_replace('/\s+/', '', $phone);
        }

        if (($address = self::string($contact, 'address')) !== null) {
            $result['address'] = $address;
        }

        return $result === [] ? null : $result;
    }

    /**
     * The agency's social links, keyed by lower-cased platform. CoreX only
     * returns the platforms that are set, and each value may be a full URL or a
     * bare handle, so normalise everything to an absolute URL and drop empties.
     * Returns [] when none are set so the frontend hides the icon row.
     *
     * @param  array<string, mixed>  $agency
     * @return array<string, string>
     */
    protected static function socials(array $agency): array
    {
        $socials = Arr::get($agency, 'social');

        if (! is_array($socials)) {
            return [];
        }

        $normalised = [];

        foreach ($socials as $platform => $value) {
            if (! is_string($platform) || ! is_string($value) || trim($value) === '') {
                continue;
            }

            $normalised[strtolower($platform)] = self::socialUrl(strtolower($platform), trim($value));
        }

        return $normalised;
    }

    /**
     * Build an absolute URL for a social link. Full URLs pass through; bare
     * handles are expanded against the platform's canonical profile path.
     */
    protected static function socialUrl(string $platform, string $value): string
    {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $handle = ltrim($value, '@/');

        return match ($platform) {
            'facebook' => 'https://facebook.com/'.$handle,
            'instagram' => 'https://instagram.com/'.$handle,
            'linkedin' => 'https://linkedin.com/'.$handle,
            'youtube' => 'https://youtube.com/@'.$handle,
            default => 'https://'.$handle,
        };
    }

    /**
     * The opening-hours rows. Each row needs both a days label and an hours
     * value (which may read "Closed"); incomplete rows are dropped. Returns []
     * when none are configured so the frontend hides the whole block.
     *
     * @param  array<string, mixed>  $agency
     * @return array<int, array{days: string, hours: string}>
     */
    protected static function openHours(array $agency): array
    {
        $rows = Arr::get($agency, 'open_hours');

        if (! is_array($rows)) {
            return [];
        }

        $result = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $days = self::string($row, 'days');
            $hours = self::string($row, 'hours');

            if ($days === null || $hours === null) {
                continue;
            }

            $result[] = ['days' => $days, 'hours' => $hours];
        }

        return $result;
    }

    /**
     * The branding colours, keyed in camelCase. Only the colours CoreX sets are
     * included; returns null when none are present.
     *
     * @param  array<string, mixed>  $agency
     * @return array<string, string>|null
     */
    protected static function branding(array $agency): ?array
    {
        $branding = Arr::get($agency, 'branding');

        if (! is_array($branding)) {
            return null;
        }

        $keys = [
            'sidebar_color' => 'sidebarColor',
            'icon_color' => 'iconColor',
            'default_color' => 'defaultColor',
            'button_color' => 'buttonColor',
        ];

        $result = [];

        foreach ($keys as $source => $destination) {
            if (($value = self::string($branding, $source)) !== null) {
                $result[$destination] = $value;
            }
        }

        return $result === [] ? null : $result;
    }

    /**
     * Pull a trimmed string value from $source[$key], collapsing missing or
     * empty values to null.
     *
     * @param  array<string, mixed>  $source
     */
    protected static function string(array $source, string $key): ?string
    {
        $value = Arr::get($source, $key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
