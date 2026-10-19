<?php

namespace App\Support;

use App\Http\Controllers\Admin\MediaUploadController;
use App\Models\ListingSeo;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds server-rendered SEO meta for the dynamic, CoreX-backed pages
 * (individual properties, agents, articles and branches) and the fixed tool
 * pages that aren't CMS-managed.
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
     * Listing index routes whose ?page=N views are distinct, indexable pages.
     */
    public const PAGINATED_ROUTES = ['for-sale', 'to-rent', 'hfc-exclusive', 'sold', 'agents'];

    /**
     * The document title for a resolved SEO page — the single rule shared by
     * the Blade <title>, the SEO partial and the Inertia `seo.title` prop, so
     * the client-rendered title can never drift from the server one.
     */
    public static function titleFor(?Page $page): string
    {
        return $page?->meta_title ?: ($page?->name ?: (string) config('app.name'));
    }

    /**
     * The canonical URL for the current request. An admin-entered canonical
     * wins; otherwise the query-free URL. On the listing index pages, page 2+
     * keep their own `?page=N` (and only that — filters still canonicalise to
     * the base path). A front-controller "/index.php" is never part of it.
     */
    public static function canonicalUrl(?Page $page, Request $request): string
    {
        $canonical = self::withoutIndexPhp($page?->canonical_url ?: $request->url());

        $pageNumber = $request->query('page');
        $pageNumber = is_string($pageNumber) && ctype_digit($pageNumber) ? (int) $pageNumber : 1;

        if ($pageNumber > 1 && in_array($request->route()?->getName(), self::PAGINATED_ROUTES, true)) {
            return strtok($canonical, '?').'?page='.$pageNumber;
        }

        return $canonical;
    }

    /**
     * Strip a front-controller "/index.php" segment from a URL.
     */
    public static function withoutIndexPhp(string $url): string
    {
        return (string) preg_replace('#/index\.php(?=/|\?|$)#', '', $url, 1);
    }

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
            'meta_title' => self::listingTitle($property),
            'meta_description' => self::clip(
                Arr::get($property, 'description') ?: self::listingSummary($property),
                self::DESCRIPTION_LIMIT,
            ),
            'canonical_url' => $canonical,
            'og_image' => Arr::get($property, 'image'),
            'og_image_alt' => $headline,
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
        $suffix = self::suffix();

        // Keep the designation only when the whole thing fits; never cut it mid-word.
        $title = $designation !== '' && mb_strlen("{$name} – {$designation}{$suffix}") <= self::TITLE_LIMIT
            ? "{$name} – {$designation}{$suffix}"
            : self::title($name);

        $description = self::clip(
            Arr::get($agent, 'about') ?: "Get in touch with {$name} at ".self::siteName().' for expert property advice along the KwaZulu-Natal coast.',
            self::DESCRIPTION_LIMIT,
        );

        return self::page([
            'name' => $name,
            'meta_title' => $title,
            'meta_description' => $description,
            'canonical_url' => $canonical,
            'og_image' => self::realImage(Arr::get($agent, 'photo')),
            'og_image_alt' => $designation !== '' ? "{$name}, {$designation}" : $name,
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
            'og_image_alt' => $title,
        ]);
    }

    /**
     * SEO meta for the "Our Branches" overview.
     */
    public static function forBranches(string $canonical): Page
    {
        return self::page([
            'name' => 'Our Branches',
            'meta_title' => self::title('Our Branches'),
            'meta_description' => 'Find your nearest '.self::siteName().' office on the KwaZulu-Natal South Coast — addresses, phone numbers and the agents in each branch.',
            'canonical_url' => $canonical,
        ]);
    }

    /**
     * SEO meta for a single branch page.
     *
     * @param  array<string, mixed>  $branch  The mapped branch (BranchMapper::map).
     */
    public static function forBranch(array $branch, string $canonical): Page
    {
        $name = trim((string) (Arr::get($branch, 'tradingName') ?: 'Our Office'));
        $town = trim((string) Arr::get($branch, 'town'));
        $inTown = $town !== '' && ! Str::contains($name, $town, true);

        $heading = $inTown ? "{$name} – Estate Agents in {$town}" : "{$name} Estate Agents";
        $title = self::fits($heading.self::suffix()) || self::fits($heading) ? self::title($heading) : self::title($name);

        // Branches span both coasts (e.g. Shelly Beach and Ballito), so don't say "South Coast".
        $where = $inTown ? " in {$town}" : '';
        $phone = trim((string) Arr::get($branch, 'phone'));
        $description = "Visit our {$name} office{$where}, ".self::siteName().'’s local branch for buying, selling and renting property on the KwaZulu-Natal coast.'
            .($phone !== '' ? " Call {$phone}." : '');

        return self::page([
            'name' => $name,
            'meta_title' => $title,
            'meta_description' => self::clip($description, self::DESCRIPTION_LIMIT),
            'canonical_url' => $canonical,
            'og_image' => self::realImage(Arr::get($branch, 'logo')),
            'og_image_alt' => $name,
        ]);
    }

    /**
     * Fixed meta for the standalone tool/legal pages that have no CMS row, or
     * null for any other route.
     */
    public static function forRoute(?string $routeName): ?Page
    {
        $site = self::siteName();

        $meta = match ($routeName) {
            'bond-calculator' => [
                'name' => 'Bond Calculator',
                'meta_title' => self::title('Bond Repayment Calculator'),
                'meta_description' => 'Work out your monthly home loan repayments with our free bond calculator. Adjust the price, deposit, interest rate and term to plan your purchase.',
            ],
            'privacy-policy' => [
                'name' => 'Privacy Policy',
                'meta_title' => self::title('Privacy Policy'),
                'meta_description' => "How {$site} collects, uses and protects your personal information in line with the Protection of Personal Information Act (POPIA).",
            ],
            default => null,
        };

        return $meta === null ? null : self::page([...$meta, 'canonical_url' => route($routeName)]);
    }

    /**
     * Width/height of an og:image when we can know it — i.e. an admin upload
     * served from our own disk. Remote (CoreX) images return null.
     *
     * @return array{width: int, height: int}|null
     */
    public static function imageSize(?string $url): ?array
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (! str_starts_with($url, url('media/uploads').'/')) {
            return null;
        }

        $file = basename((string) parse_url($url, PHP_URL_PATH));

        try {
            $size = @getimagesize(Storage::disk('public')->path(MediaUploadController::DIRECTORY.'/'.$file));
        } catch (\Throwable) {
            return null;
        }

        return is_array($size) && $size[0] > 0 ? ['width' => $size[0], 'height' => $size[1]] : null;
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
     * A listing title built from its structured fields, e.g.
     * "3 Bed House for Sale in Shelly Beach | R 1 950 000 | Home Finders Coastal".
     *
     * The longest variant that fits the title limit wins: the site suffix goes
     * first, then the price, then the bed count. The suburb is never cut, and
     * the province is never included. Listings with no type or location fall
     * back to their headline.
     *
     * @param  array<string, mixed>  $property
     */
    public static function listingTitle(array $property): string
    {
        $type = Str::ucfirst(trim((string) (Arr::get($property, 'propertyType') ?: Arr::get($property, 'category'))));
        $place = trim((string) (Arr::get($property, 'suburb') ?: Arr::get($property, 'town')));

        if ($type === '' && $place === '') {
            return self::title(trim((string) (Arr::get($property, 'title') ?: 'Property for sale')));
        }

        $type = $type !== '' ? $type : 'Property';
        $deal = Str::contains(strtolower((string) Arr::get($property, 'listingType')), ['rent', 'let']) ? 'to Rent' : 'for Sale';
        $beds = (int) Arr::get($property, 'beds', 0);
        $in = $place !== '' ? " in {$place}" : '';

        $minimal = "{$type} {$deal}{$in}";
        $core = $beds > 0 ? "{$beds} Bed {$minimal}" : $minimal;
        $price = Arr::get($property, 'priceAmount') !== null ? trim((string) Arr::get($property, 'price')) : '';
        $suffix = self::suffix();

        $candidates = $price !== ''
            ? ["{$core} | {$price}{$suffix}", "{$core} | {$price}", "{$core}{$suffix}", $core, $minimal]
            : ["{$core}{$suffix}", $core, $minimal];

        foreach ($candidates as $candidate) {
            if (self::fits($candidate)) {
                return $candidate;
            }
        }

        return $minimal;
    }

    /**
     * Suffix a heading with the site name when it fits within the title limit,
     * e.g. "Our Branches | Home Finders Coastal". A heading too long for the
     * suffix is shown on its own, cut at a word boundary if still too long.
     */
    protected static function title(string $heading): string
    {
        $heading = trim($heading);

        if (self::fits($heading.self::suffix())) {
            return $heading.self::suffix();
        }

        return self::fits($heading) ? $heading : self::clip($heading, self::TITLE_LIMIT - 1);
    }

    protected static function suffix(): string
    {
        return ' | '.self::siteName();
    }

    protected static function fits(string $title): bool
    {
        return mb_strlen($title) <= self::TITLE_LIMIT;
    }

    /**
     * An image URL, or null for CoreX's placeholder stand-ins (which make poor
     * share cards and are not the entity's own photo).
     */
    protected static function realImage(mixed $url): ?string
    {
        $url = is_string($url) ? trim($url) : '';

        return $url !== '' && ! str_contains($url, 'placehold.co') ? $url : null;
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

        return Str::limit($text, $limit, '…', preserveWords: true);
    }

    /**
     * The site name for titles and og:site_name: the admin-set name, falling
     * back to the app name (also when the settings table isn't migrated).
     */
    public static function siteName(): string
    {
        try {
            $name = trim((string) SiteSetting::current()->site_name);
        } catch (\Throwable) {
            $name = '';
        }

        return $name !== '' ? $name : (string) config('app.name');
    }
}
