<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Transforms a raw CoreX agent resource into the shape the React Agents page
 * expects (see resources/js/pages/agents.tsx).
 *
 * CoreX exposes id, name, designation, email, phone, cell, photo_url and the
 * richer about/socials profile fields for agents. It does not provide a
 * coverage area, and designation/about may be null for some agents, so the
 * frontend hides them when absent.
 */
class AgentMapper
{
    /**
     * @param  array<int, array<string, mixed>>  $agents
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $agents): array
    {
        return array_values(array_map(
            static fn (array $agent): array => self::map($agent),
            $agents,
        ));
    }

    /**
     * @param  array<string, mixed>  $agent
     * @return array{
     *     id: int|string,
     *     name: string,
     *     designation: string|null,
     *     phone: string,
     *     email: string,
     *     photo: string,
     *     about: string|null,
     *     socials: array<string, string>,
     * }
     */
    public static function map(array $agent): array
    {
        return [
            'id' => Arr::get($agent, 'id', ''),
            'name' => (string) (Arr::get($agent, 'name') ?: 'Agent'),
            'designation' => self::designation($agent),
            'phone' => (string) (Arr::get($agent, 'cell') ?? Arr::get($agent, 'phone', '')),
            'email' => (string) Arr::get($agent, 'email', ''),
            'photo' => self::photo($agent),
            'about' => self::about($agent),
            'socials' => self::socials($agent),
        ];
    }

    /**
     * Build the name-based URL slug for an agent, e.g. "elize-reichel". Used as
     * the agent.show path segment in place of the raw CoreX id. Falls back to
     * the bare id when the agent has no usable name.
     *
     * The matching id is resolved back from the slug by the controller, so the
     * id stays out of the URL while old "/agents/{id}" links keep working.
     *
     * @param  array<string, mixed>  $agent
     */
    public static function slug(array $agent): string
    {
        $slug = Str::slug((string) (Arr::get($agent, 'name') ?: ''));

        return $slug !== '' ? $slug : (string) Arr::get($agent, 'id', '');
    }

    /**
     * The agent's role/title (e.g. "Principal Property Practitioner"). CoreX
     * may return null or omit it, so collapse empty values to null and let the
     * frontend hide the sub-line entirely.
     *
     * @param  array<string, mixed>  $agent
     */
    protected static function designation(array $agent): ?string
    {
        $designation = Arr::get($agent, 'designation');

        return is_string($designation) && trim($designation) !== ''
            ? trim($designation)
            : null;
    }

    /**
     * @param  array<string, mixed>  $agent
     */
    protected static function photo(array $agent): string
    {
        $photo = Arr::get($agent, 'photo_url');

        return is_string($photo) && $photo !== ''
            ? $photo
            : 'https://placehold.co/600x600?text=Agent';
    }

    /**
     * The agent's free-text "Get to Know Me" bio. CoreX may return null, so
     * collapse empty values to null and let the frontend hide the section.
     *
     * @param  array<string, mixed>  $agent
     */
    protected static function about(array $agent): ?string
    {
        $about = Arr::get($agent, 'about');

        return is_string($about) && trim($about) !== '' ? trim($about) : null;
    }

    /**
     * The agent's social links, keyed by lower-cased platform. CoreX only
     * returns the platforms the agent filled in, and a value may be either a
     * full URL or a bare handle (e.g. Instagram), so normalise everything to an
     * absolute URL and drop anything empty. Returns an empty array when the
     * agent has no socials, so the frontend hides the icon row.
     *
     * @param  array<string, mixed>  $agent
     * @return array<string, string>
     */
    protected static function socials(array $agent): array
    {
        $socials = Arr::get($agent, 'socials');

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
            'linkedin' => 'https://linkedin.com/in/'.$handle,
            'youtube' => 'https://youtube.com/@'.$handle,
            'twitter', 'x' => 'https://x.com/'.$handle,
            'tiktok' => 'https://tiktok.com/@'.$handle,
            default => 'https://'.$handle,
        };
    }
}
