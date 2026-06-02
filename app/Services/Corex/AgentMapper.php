<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Transforms a raw CoreX agent resource into the shape the React Agents page
 * expects (see resources/js/pages/agents.tsx).
 *
 * CoreX exposes id, name, email, phone, cell and photo_url for agents — it
 * does not provide a job title or coverage area, so those are left empty and
 * the frontend hides them when absent.
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
            'phone' => (string) (Arr::get($agent, 'cell') ?? Arr::get($agent, 'phone', '')),
            'email' => (string) Arr::get($agent, 'email', ''),
            'photo' => self::photo($agent),
        ];
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
