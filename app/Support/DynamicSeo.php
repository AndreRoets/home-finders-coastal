<?php

namespace App\Support;

use App\Models\ListingSeo;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Builds server-rendered SEO meta for the dynamic, CoreX-backed pages
 * (individual properties, agents and articles) that aren't CMS-managed.
 *
 * Each builder returns a transient (unsaved) {@see Page} carrying a real
 * meta title, description, canonical URL and social image derived from the
 * entity. Sharing it as `seoPage` lets the existing SEO Blade partial render
 * these pages identically to managed pages — so every listing has its own
 * crawlable title and description instead of one generic site-wide default.
 */
class DynamicSeo
{
    /**
     * Meta titles/descriptions Google starts truncating around these lengths.
     */
    protected const TITLE_LIMIT = 60;

    protected const DESCRIPTION_LIMIT = 160;

    /**
     * SEO meta for a single property detail page: the auto-generated defaults
     * with any admin-authored per-listing overrides layered on top.
     *
     * @param  array<string, mixed>  $property  The mapped listing (ListingMapper::detail).
     */
    public static function forListing(array $property, string $canonical): Page
    {
        return self::page(array_merge(
            self::listingDefaults($property, $canonical),
            self::listingOverride(Arr::get($property, 'id')),
        ));
    }

    /**
     * The auto-generated SEO meta for a listing, before any admin override.
     * Also used by the admin editor to show what each field falls back to.
     *
     * @param  array<string, mixed>  $property
     * @return array<string, string|null>
     */
    public static function listingDefaults(array $property, string $canonical): array
    {
        $headline = trim((string) (Arr::get($property, 'title') ?: 'Property for sale'));

        return [
            'name' => $headline,
            'meta_title' => self::title($headline),
            'meta_description' => self::clip(
                Arr::get($property, 'description') ?: self::listingSummary($property),
                self::DESCRIPTION_LIMIT,
            ),
            'canonical_url' => $canonical,
            'og_image' => Arr::get($property, 'image'),
        ];
    }

    /**
     * The admin-authored override columns for a listing, or [] when none/unset.
     * Wrapped so a missing table (fresh install) never breaks the public page.
     *
     * @return array<string, string>
     */
    protected static function listingOverride(mixed $listingId): array
    {
        if ($listingId === null || $listingId === '') {
            return [];
        }

        try {
            return ListingSeo::query()
                ->where('listing_id', (string) $listingId)
                ->first()
                ?->overrides() ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * SEO meta for an agent profile page.
     *
     * @param  array<string, mixed>  $agent  The mapped agent (AgentMapper::map).
     */
    public static function forAgent(array $agent, string $canonical): Page
    {
        $name = trim((string) (Arr::get($agent, 'name') ?: 'Estate Agent'));
        $designation = trim((string) Arr::get($agent, 'designation'));
        $heading = $designation !== '' ? "{$name} – {$designation}" : $name;

        $description = self::clip(
            Arr::get($agent, 'about') ?: "Get in touch with {$name} at ".self::siteName().' for expert property advice along the KwaZulu-Natal coast.',
            self::DESCRIPTION_LIMIT,
        );

        return self::page([
            'name' => $name,
            'meta_title' => self::title($heading),
            'meta_description' => $description,
            'canonical_url' => $canonical,
            'og_image' => Arr::get($agent, 'photo'),
        ]);
    }

    /**
     * SEO meta for an article page.
     *
     * @param  array<string, mixed>  $article  The mapped article (ArticleMapper::map).
     */
    public static function forArticle(array $article, string $canonical): Page
    {
        $title = trim((string) (Arr::get($article, 'title') ?: 'Article'));
        $description = self::clip(
            Arr::get($article, 'excerpt') ?: Arr::get($article, 'body'),
            self::DESCRIPTION_LIMIT,
        );

        return self::page([
            'name' => $title,
            'meta_title' => self::title($title),
            'meta_description' => $description,
            'canonical_url' => $canonical,
            'og_type' => 'article',
            'og_image' => Arr::get($article, 'coverImage'),
        ]);
    }

    /**
     * A transient Page with sensible defaults for the dynamic SEO meta.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected static function page(array $attributes): Page
    {
        return new Page(array_merge([
            'robots_index' => true,
            'robots_follow' => true,
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
        ], array_filter($attributes, static fn ($value): bool => $value !== null && $value !== '')));
    }

    /**
     * Suffix a heading with the site name, e.g. "3 Bed Apartment | Home Finders Coastal".
     */
    protected static function title(string $heading): string
    {
        $suffix = ' | '.self::siteName();
        $room = self::TITLE_LIMIT - strlen($suffix);

        return self::clip($heading, max($room, 20)).$suffix;
    }

    /**
     * A one-line, human summary of a listing, used when CoreX gives no
     * description: "3 bedroom Apartment for sale in Margate — R1 950 000".
     *
     * @param  array<string, mixed>  $property
     */
    protected static function listingSummary(array $property): string
    {
        $beds = (int) Arr::get($property, 'beds', 0);
        $type = trim((string) (Arr::get($property, 'propertyType') ?: Arr::get($property, 'category') ?: 'property'));
        $deal = Str::contains(strtolower((string) Arr::get($property, 'listingType')), ['rent', 'let']) ? 'to rent' : 'for sale';
        $location = trim((string) Arr::get($property, 'location'));
        $price = trim((string) Arr::get($property, 'price'));

        $summary = trim(($beds > 0 ? "{$beds} bedroom " : '').$type." {$deal}");

        if ($location !== '') {
            $summary .= " in {$location}";
        }

        if ($price !== '') {
            $summary .= " — {$price}";
        }

        return Str::ucfirst($summary).'.';
    }

    /**
     * Strip HTML, collapse whitespace and truncate to a word boundary.
     */
    protected static function clip(mixed $value, int $limit): string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        return Str::limit($text, $limit, '…');
    }

    protected static function siteName(): string
    {
        return SiteSetting::current()->site_name ?: (string) config('app.name');
    }
}
