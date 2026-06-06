<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Transforms a raw CoreX testimonial resource into the shape the React frontend
 * expects (see resources/js/components/public/testimonials.ts).
 *
 * CoreX exposes id, author, rating (1–5 or null), body, date and the agent the
 * testimonial is about (agent_id + a nested agent {id, name}, either may be
 * null when the testimonial is unattributed).
 */
class TestimonialMapper
{
    /**
     * @param  array<int, array<string, mixed>>  $testimonials
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $testimonials): array
    {
        return array_values(array_map(
            static fn (array $testimonial): array => self::map($testimonial),
            $testimonials,
        ));
    }

    /**
     * @param  array<string, mixed>  $testimonial
     * @return array{
     *     id: int|string,
     *     author: string,
     *     rating: int|null,
     *     body: string,
     *     date: string|null,
     *     agent: array{id: int|string, name: string}|null,
     * }
     */
    public static function map(array $testimonial): array
    {
        return [
            'id' => Arr::get($testimonial, 'id', ''),
            'author' => (string) (Arr::get($testimonial, 'author') ?: 'Anonymous'),
            'rating' => self::rating($testimonial),
            'body' => (string) Arr::get($testimonial, 'body', ''),
            'date' => self::date($testimonial),
            'agent' => self::agent($testimonial),
        ];
    }

    /**
     * Normalise the rating to an int between 1 and 5, or null when absent or
     * out of range, so the frontend only renders stars when present.
     *
     * @param  array<string, mixed>  $testimonial
     */
    protected static function rating(array $testimonial): ?int
    {
        $rating = Arr::get($testimonial, 'rating');

        if (! is_numeric($rating)) {
            return null;
        }

        $rating = (int) round((float) $rating);

        return ($rating >= 1 && $rating <= 5) ? $rating : null;
    }

    /**
     * @param  array<string, mixed>  $testimonial
     */
    protected static function date(array $testimonial): ?string
    {
        $date = Arr::get($testimonial, 'date');

        return is_string($date) && $date !== '' ? $date : null;
    }

    /**
     * The agent the testimonial is about, collapsed to {id, name} for linking.
     * Falls back to a bare agent_id when CoreX omits the nested agent object.
     *
     * @param  array<string, mixed>  $testimonial
     * @return array{id: int|string, name: string}|null
     */
    protected static function agent(array $testimonial): ?array
    {
        $agent = Arr::get($testimonial, 'agent');

        if (is_array($agent) && Arr::has($agent, 'id')) {
            return [
                'id' => $agent['id'],
                'name' => (string) (Arr::get($agent, 'name') ?: 'Agent'),
            ];
        }

        return null;
    }
}
