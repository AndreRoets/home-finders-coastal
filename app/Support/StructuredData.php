<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds the server-rendered schema.org JSON-LD for the public site.
 *
 * Controllers share their page-specific nodes (listing, agent, article,
 * breadcrumbs) as the `structuredData` view variable; the SEO partial wraps
 * them, together with the agency's Organization node, in a single @graph.
 * Everything is generated here from mapped data and JSON-encoded with tag
 * escaping, so no CoreX or admin string can break out of the <script> tag.
 * Missing fields are dropped rather than emitted empty.
 */
class StructuredData
{
    public const AREA_SERVED = 'KwaZulu-Natal South Coast';

    /**
     * The encoded JSON-LD document for a public page: the Organization node
     * plus the given page nodes. Returns null when there is nothing to emit.
     *
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function render(array $nodes = []): ?string
    {
        $graph = array_values(array_filter([self::organization(), ...$nodes]));

        return $graph === [] ? null : self::encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ]);
    }

    /**
     * JSON for a <script type="application/ld+json"> body. JSON_HEX_TAG turns
     * every < and > into < / >, so "</script>" can never appear.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE,
        );
    }

    /**
     * The agency as a RealEstateAgent, from the site settings and the CoreX
     * agency profile. Fails soft to the site name and URL on a CoreX error.
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        try {
            $agency = AgencyMapper::map(app(CorexClient::class)->agency()) ?? [];
        } catch (Throwable) {
            $agency = [];
        }

        try {
            $siteName = trim((string) SiteSetting::current()->site_name);
        } catch (Throwable) {
            $siteName = '';
        }

        $address = Arr::get($agency, 'contact.address');

        return self::filled([
            '@type' => 'RealEstateAgent',
            '@id' => self::organizationId(),
            'name' => $siteName ?: (Arr::get($agency, 'tradingName') ?: (Arr::get($agency, 'name') ?: config('app.name'))),
            'url' => url('/'),
            'logo' => Arr::get($agency, 'logo') ?: asset('images/hfc-logo.png'),
            'image' => Arr::get($agency, 'logo') ?: asset('images/hfc-logo.png'),
            'telephone' => Arr::get($agency, 'contact.phone'),
            'email' => Arr::get($agency, 'contact.email'),
            'address' => $address ? [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressRegion' => 'KwaZulu-Natal',
                'addressCountry' => 'ZA',
            ] : null,
            'areaServed' => ['@type' => 'Place', 'name' => self::AREA_SERVED],
            'sameAs' => array_values((array) Arr::get($agency, 'socials', [])),
        ]);
    }

    /**
     * A property detail page as a RealEstateListing with its offer and the
     * accommodation on offer.
     *
     * @param  array<string, mixed>  $property  The mapped listing (ListingMapper::detail).
     * @return array<string, mixed>
     */
    public static function listing(array $property, string $url): array
    {
        $isRental = Str::contains(strtolower((string) Arr::get($property, 'listingType')), ['rent', 'let']);
        $amount = Arr::get($property, 'priceAmount');
        $images = array_values(array_filter((array) Arr::get($property, 'images', []), 'is_string'));
        $beds = (int) Arr::get($property, 'beds', 0);
        $baths = (int) Arr::get($property, 'baths', 0);

        return self::filled([
            '@type' => 'RealEstateListing',
            '@id' => $url.'#listing',
            'url' => $url,
            'name' => Arr::get($property, 'title'),
            'description' => self::text(Arr::get($property, 'description'), 5000),
            'image' => array_slice($images !== [] ? $images : [Arr::get($property, 'image')], 0, 10),
            'datePosted' => self::date(Arr::get($property, 'listedAt')),
            'offers' => $amount !== null ? [
                '@type' => 'Offer',
                'price' => $amount,
                'priceCurrency' => 'ZAR',
                'businessFunction' => $isRental ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
                'offeredBy' => ['@id' => self::organizationId()],
            ] : null,
            'mainEntity' => [
                '@type' => self::accommodationType((string) Arr::get($property, 'propertyType')),
                'numberOfRooms' => $beds ?: null,
                'numberOfBedrooms' => $beds ?: null,
                'numberOfBathroomsTotal' => $baths ?: null,
                'floorSize' => self::floorSize(Arr::get($property, 'area')),
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => Arr::get($property, 'suburb') ?: Arr::get($property, 'town'),
                    'addressRegion' => Arr::get($property, 'suburb') ? Arr::get($property, 'town') : null,
                    'addressCountry' => 'ZA',
                ],
            ],
        ]);
    }

    /**
     * An agent profile as a Person who works for the agency.
     *
     * @param  array<string, mixed>  $agent  The mapped agent (AgentMapper::map).
     * @return array<string, mixed>
     */
    public static function agent(array $agent, string $url): array
    {
        return self::filled([
            '@type' => 'Person',
            '@id' => $url.'#person',
            'url' => $url,
            'name' => Arr::get($agent, 'name'),
            'jobTitle' => Arr::get($agent, 'designation'),
            'image' => self::realImage(Arr::get($agent, 'photo')),
            'telephone' => Arr::get($agent, 'phone'),
            'email' => Arr::get($agent, 'email'),
            'description' => self::text(Arr::get($agent, 'about'), 1000),
            'worksFor' => ['@id' => self::organizationId()],
            'sameAs' => array_values((array) Arr::get($agent, 'socials', [])),
        ]);
    }

    /**
     * An article page as an Article, credited to its author when known.
     *
     * @param  array<string, mixed>  $article  The mapped article (ArticleMapper::map).
     * @param  array<string, mixed>|null  $author  The mapped author agent, if resolved.
     * @return array<string, mixed>
     */
    public static function article(array $article, string $url, ?array $author = null): array
    {
        return self::filled([
            '@type' => 'Article',
            '@id' => $url.'#article',
            'url' => $url,
            'mainEntityOfPage' => $url,
            'headline' => self::text(Arr::get($article, 'title'), 110),
            'description' => self::text(Arr::get($article, 'excerpt'), 300),
            'image' => Arr::get($article, 'coverImage'),
            'datePublished' => self::date(Arr::get($article, 'date')),
            'author' => $author !== null ? self::filled([
                '@type' => 'Person',
                'name' => Arr::get($author, 'name'),
                'url' => route('agents.show', Str::slug((string) Arr::get($author, 'name')) ?: Arr::get($author, 'id')),
            ]) : ['@id' => self::organizationId()],
            'publisher' => ['@id' => self::organizationId()],
        ]);
    }

    /**
     * A BreadcrumbList from ordered [name, url] pairs.
     *
     * @param  list<array{0: string, 1: string}>  $crumbs
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $crumb, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => self::text($crumb[0], 110),
                    'item' => $crumb[1],
                ],
                $crumbs,
                array_keys($crumbs),
            ),
        ];
    }

    /**
     * The "Home" crumb every breadcrumb trail starts with.
     *
     * @return array{0: string, 1: string}
     */
    public static function homeCrumb(): array
    {
        return ['Home', url('/')];
    }

    protected static function organizationId(): string
    {
        return url('/').'#organization';
    }

    /**
     * The closest schema.org accommodation type for a CoreX property type.
     */
    protected static function accommodationType(string $propertyType): string
    {
        $type = strtolower($propertyType);

        return match (true) {
            Str::contains($type, ['apartment', 'flat', 'penthouse', 'simplex', 'duplex']) => 'Apartment',
            Str::contains($type, ['house', 'townhouse', 'cluster']) => 'House',
            default => 'Accommodation',
        };
    }

    /**
     * Floor size from the mapped display area ("120 m²"), or null.
     *
     * @return array<string, mixed>|null
     */
    protected static function floorSize(mixed $area): ?array
    {
        if (! is_string($area) || preg_match('/^([\d\s]+)\s*m²$/u', trim($area), $matches) !== 1) {
            return null;
        }

        return ['@type' => 'QuantitativeValue', 'value' => (int) preg_replace('/\D/', '', $matches[1]), 'unitCode' => 'MTK'];
    }

    /**
     * An ISO 8601 date from a CoreX timestamp, or null when unparseable.
     */
    protected static function date(mixed $value): ?string
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

    /**
     * Plain text: tags stripped, whitespace collapsed, cut at a word boundary.
     */
    protected static function text(mixed $value, int $limit): ?string
    {
        $text = trim((string) preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5))));

        return $text !== '' ? Str::limit($text, $limit, '…', preserveWords: true) : null;
    }

    /**
     * An image URL, or null for placeholder stand-ins.
     */
    protected static function realImage(mixed $url): ?string
    {
        return is_string($url) && $url !== '' && ! str_contains($url, 'placehold.co') ? $url : null;
    }

    /**
     * Recursively drop null, empty-string and empty-array values.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    protected static function filled(array $node): array
    {
        $result = [];

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $value = array_is_list($value) ? array_values(array_filter($value, static fn ($item): bool => $item !== null && $item !== '')) : self::filled($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            // A nested node left with only its @type carries no information.
            if (is_array($value) && array_keys($value) === ['@type']) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
