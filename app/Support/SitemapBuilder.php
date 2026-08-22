<?php

namespace App\Support;

use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\AgentMapper;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Builds the site's XML sitemap.
 *
 * Single source of truth shared by the public /sitemap.xml endpoint and the
 * admin Sitemap panel, so what an admin previews is exactly what Google fetches.
 *
 * Every CoreX-backed section fails soft: an outage drops that section's URLs
 * rather than breaking the sitemap.
 */
class SitemapBuilder
{
    /**
     * @var list<array{key: string, label: string, description: string, dynamic: bool, urls: list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>}>|null
     */
    protected ?array $sections = null;

    public function __construct(protected CorexClient $corex) {}

    /**
     * Every sitemap section, in the order they are written to the XML.
     *
     * @return list<array{key: string, label: string, description: string, dynamic: bool, urls: list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>}>
     */
    public function sections(): array
    {
        return $this->sections ??= [
            $this->section('pages', 'Managed pages', 'Slugs and SEO managed under Pages & SEO.', false, fn () => $this->managedPages()),
            $this->section('static', 'Standalone pages', 'Fixed tools and legal pages.', false, fn () => $this->staticPages()),
            $this->section('branches', 'Branches', 'The offices overview and each branch page.', true, fn () => $this->branches()),
            $this->section('properties', 'Properties', 'Every listing syndicated from CoreX.', true, fn () => $this->properties()),
            $this->section('agents', 'Agents', 'Every public agent profile.', true, fn () => $this->agents()),
            $this->section('articles', 'Articles', 'Published articles from CoreX.', true, fn () => $this->articles()),
        ];
    }

    /**
     * The rendered sitemap document.
     */
    public function toXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($this->sections() as $section) {
            foreach ($section['urls'] as $url) {
                $xml .= "  <url>\n";
                $xml .= '    <loc>'.e($url['loc'])."</loc>\n";

                foreach (['lastmod', 'changefreq', 'priority'] as $tag) {
                    if (($url[$tag] ?? null) !== null && $url[$tag] !== '') {
                        $xml .= '    <'.$tag.'>'.e($url[$tag]).'</'.$tag.">\n";
                    }
                }

                $xml .= "  </url>\n";
            }
        }

        return $xml.'</urlset>';
    }

    /**
     * How many URLs the sitemap currently advertises.
     */
    public function total(): int
    {
        return array_sum(array_map(fn (array $section) => count($section['urls']), $this->sections()));
    }

    /**
     * robots.txt — admin-managed, falling back to a sane default that points
     * crawlers at the sitemap and keeps the admin panel out of the index.
     */
    public function robotsTxt(): string
    {
        $custom = (string) SiteSetting::current()->robots_txt;

        if (trim($custom) !== '') {
            return $custom;
        }

        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /settings',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ]);
    }

    /**
     * Whether the served robots.txt tells crawlers where the sitemap lives.
     */
    public function robotsReferencesSitemap(): bool
    {
        return str_contains(strtolower($this->robotsTxt()), 'sitemap:');
    }

    /**
     * Wrap a section's URL builder so one failing section degrades to no URLs
     * instead of taking the whole sitemap down with it.
     *
     * @param  bool  $dynamic  Whether the section's URLs come from CoreX.
     * @param  callable(): list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>  $urls
     * @return array{key: string, label: string, description: string, dynamic: bool, urls: list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>}
     */
    protected function section(string $key, string $label, string $description, bool $dynamic, callable $urls): array
    {
        try {
            $resolved = $urls();
        } catch (Throwable) {
            $resolved = [];
        }

        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'dynamic' => $dynamic,
            'urls' => $resolved,
        ];
    }

    /**
     * Active CMS pages that are allowed in the index. A noindex page is left
     * out — listing it would only ask Google to crawl a page it must then drop.
     *
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function managedPages(): array
    {
        return Page::query()
            ->where('is_active', true)
            ->where('robots_index', true)
            ->whereIn('key', array_keys(PageRoutes::actions()))
            ->orderBy('id')
            ->get()
            ->map(fn (Page $page) => $this->url(
                url($page->path()),
                optional($page->updated_at)->toAtomString(),
                $page->sitemap_frequency,
                $page->sitemap_priority,
            ))
            ->all();
    }

    /**
     * Fixed routes that carry no CMS record of their own.
     *
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function staticPages(): array
    {
        return [
            $this->url(route('bond-calculator'), null, 'yearly', '0.5'),
            $this->url(route('privacy-policy'), null, 'yearly', '0.2'),
        ];
    }

    /**
     * The branches pages, which only exist while the agency has the feature on.
     *
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function branches(): array
    {
        $agency = AgencyMapper::map($this->corex->agency());

        if (($agency['show']['branches'] ?? false) !== true) {
            return [];
        }

        $urls = [$this->url(route('branches'), null, 'monthly', '0.6')];

        foreach ($this->corex->branches() as $branch) {
            $id = Arr::get($branch, 'id');

            if ($id !== null && $id !== '') {
                $urls[] = $this->url(route('branches.show', $id), null, 'monthly', '0.5');
            }
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function properties(): array
    {
        $urls = [];

        foreach ($this->corex->listings() as $listing) {
            $urls[] = $this->url(
                route('property.show', ListingMapper::slug($listing)),
                $this->timestamp(Arr::get($listing, 'updated_at')),
                'daily',
                '0.8',
            );
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function agents(): array
    {
        $urls = [];

        foreach ($this->corex->agents() as $agent) {
            $id = Arr::get($agent, 'id');

            if ($id !== null && $id !== '') {
                $urls[] = $this->url(route('agents.show', AgentMapper::slug($agent)), null, 'weekly', '0.6');
            }
        }

        return $urls;
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}>
     */
    protected function articles(): array
    {
        $urls = [];

        foreach ($this->corex->articles() as $article) {
            $slug = trim((string) Arr::get($article, 'slug', ''));

            if ($slug !== '') {
                $urls[] = $this->url(
                    route('articles.show', $slug),
                    $this->timestamp(Arr::get($article, 'updated_at')),
                    'monthly',
                    '0.5',
                );
            }
        }

        return $urls;
    }

    /**
     * @return array{loc: string, lastmod: string|null, changefreq: string|null, priority: string|null}
     */
    protected function url(string $loc, ?string $lastmod = null, ?string $changefreq = null, ?string $priority = null): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    /**
     * Normalise a CoreX timestamp to the W3C format sitemaps expect, ignoring
     * anything unparseable.
     */
    protected function timestamp(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toAtomString();
        } catch (Throwable) {
            return null;
        }
    }
}
