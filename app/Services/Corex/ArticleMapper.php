<?php

namespace App\Services\Corex;

use Illuminate\Support\Arr;

/**
 * Transforms a raw CoreX article resource into the shape the React frontend
 * expects (see resources/js/components/public/articles.ts).
 *
 * CoreX exposes id, agent_id, title, slug, excerpt, cover_image_url, body,
 * link_url, tags, read_minutes, word_count and date. cover_image_url, excerpt
 * and link_url may be null, so the frontend renders placeholders / hides those
 * pieces when absent.
 */
class ArticleMapper
{
    /**
     * @param  array<int, array<string, mixed>>  $articles
     * @return array<int, array<string, mixed>>
     */
    public static function collection(array $articles): array
    {
        return array_values(array_map(
            static fn (array $article): array => self::map($article),
            $articles,
        ));
    }

    /**
     * @param  array<string, mixed>  $article
     * @return array{
     *     id: int|string,
     *     agentId: int|string|null,
     *     title: string,
     *     slug: string,
     *     excerpt: string|null,
     *     coverImage: string|null,
     *     body: string,
     *     linkUrl: string|null,
     *     tags: list<string>,
     *     readMinutes: int,
     *     wordCount: int,
     *     date: string|null,
     * }
     */
    public static function map(array $article): array
    {
        return [
            'id' => Arr::get($article, 'id', ''),
            'agentId' => Arr::get($article, 'agent_id'),
            'title' => (string) (Arr::get($article, 'title') ?: 'Untitled article'),
            'slug' => (string) Arr::get($article, 'slug', ''),
            'excerpt' => self::text($article, 'excerpt'),
            'coverImage' => self::url($article, 'cover_image_url'),
            'body' => (string) Arr::get($article, 'body', ''),
            'linkUrl' => self::url($article, 'link_url'),
            'tags' => self::tags($article),
            'readMinutes' => (int) Arr::get($article, 'read_minutes', 0),
            'wordCount' => (int) Arr::get($article, 'word_count', 0),
            'date' => self::text($article, 'date'),
        ];
    }

    /**
     * A non-empty string field, or null when absent/blank.
     *
     * @param  array<string, mixed>  $article
     */
    protected static function text(array $article, string $key): ?string
    {
        $value = Arr::get($article, $key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * A non-empty URL field, or null when absent/blank.
     *
     * @param  array<string, mixed>  $article
     */
    protected static function url(array $article, string $key): ?string
    {
        $value = Arr::get($article, $key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The article's tags as a clean list of non-empty strings.
     *
     * @param  array<string, mixed>  $article
     * @return list<string>
     */
    protected static function tags(array $article): array
    {
        return array_values(array_filter(
            (array) Arr::get($article, 'tags', []),
            static fn ($tag): bool => is_string($tag) && trim($tag) !== '',
        ));
    }
}
