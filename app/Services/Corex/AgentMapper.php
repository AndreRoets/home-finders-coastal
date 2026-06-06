<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Transforms a raw CoreX agent resource into the shape the React Agents page
 * expects (see resources/js/pages/agents.tsx).
 *
 * CoreX exposes id, name, designation, email, phone, cell and photo_url for
 * agents. It does not provide a coverage area, and designation may be null for
 * some agents, so the frontend hides it when absent.
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
        ];
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
}
