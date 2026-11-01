<?php

namespace App\Services\Enquiries;

use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * Credits a visitor's first touch (UTM tags, click ids, referrer) to a
 * {@see Channel}, following GA4's default channel grouping.
 *
 * The rules are fixed; the lists they match against are site settings
 * (Marketing & Analytics → Enquiry attribution), with the defaults below used
 * until someone edits them. Rules, in order — the first that matches wins:
 *
 *  1. Paid medium from a social source (or a social medium)  → Paid Social
 *  2. gclid, or any other paid medium                         → Paid Search
 *  3. fbclid                                                  → Paid Social
 *  4. Email medium or source                                  → Email
 *  5. medium "organic", or a search engine source/referrer    → Organic Search
 *  6. Social medium, social source or social referrer         → Organic Social
 *  7. medium "referral", or any other referrer without UTMs   → Referral
 *  8. No referrer and no UTMs                                 → Direct
 *  9. UTM tags that match none of the above                   → Unassigned
 *
 * Sources and referrer hosts match an entry on a whole host label ("google"
 * matches google.co.za but not googleblog-fan.com) or, for an entry with a
 * dot, on the host suffix ("t.co"). Mediums match exactly, with `*` as a
 * wildcard ("paid*").
 */
class ChannelClassifier
{
    public const DEFAULT_SEARCH_SOURCES = ['google', 'bing', 'yahoo', 'duckduckgo', 'ecosia', 'yandex', 'baidu', 'ask', 'startpage', 'search.brave.com'];

    public const DEFAULT_SOCIAL_SOURCES = ['facebook', 'fb', 'meta', 'instagram', 'ig', 'linkedin', 'lnkd.in', 'tiktok', 'x', 'twitter', 't.co', 'youtube', 'youtu.be', 'pinterest', 'reddit', 'whatsapp', 'threads.net'];

    public const DEFAULT_PAID_MEDIUMS = ['cpc', 'ppc', 'paid*', 'cpm', 'cpv', 'cpa', 'display', 'sem', 'retargeting'];

    public const DEFAULT_EMAIL_MEDIUMS = ['email', 'e-mail', 'e_mail', 'newsletter'];

    public const DEFAULT_SOCIAL_MEDIUMS = ['*social*', 'sm'];

    /**
     * @param  list<string>  $searchSources
     * @param  list<string>  $socialSources
     * @param  list<string>  $paidMediums
     * @param  list<string>  $emailMediums
     * @param  list<string>  $socialMediums
     */
    public function __construct(
        protected array $searchSources = self::DEFAULT_SEARCH_SOURCES,
        protected array $socialSources = self::DEFAULT_SOCIAL_SOURCES,
        protected array $paidMediums = self::DEFAULT_PAID_MEDIUMS,
        protected array $emailMediums = self::DEFAULT_EMAIL_MEDIUMS,
        protected array $socialMediums = self::DEFAULT_SOCIAL_MEDIUMS,
    ) {}

    /**
     * A classifier using the lists saved in the site settings, falling back to
     * the defaults for any list left blank.
     */
    public static function fromSettings(SiteSetting $settings): self
    {
        return new self(
            self::listOr($settings->channel_search_sources, self::DEFAULT_SEARCH_SOURCES),
            self::listOr($settings->channel_social_sources, self::DEFAULT_SOCIAL_SOURCES),
            self::listOr($settings->channel_paid_mediums, self::DEFAULT_PAID_MEDIUMS),
            self::listOr($settings->channel_email_mediums, self::DEFAULT_EMAIL_MEDIUMS),
            self::listOr($settings->channel_social_mediums, self::DEFAULT_SOCIAL_MEDIUMS),
        );
    }

    /**
     * Split a one-entry-per-line setting into a clean, lower-cased list.
     *
     * @return list<string>
     */
    public static function parseList(?string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $line): string => Str::lower(trim($line)),
            preg_split('/[\r\n,]+/', (string) $value) ?: [],
        ))));
    }

    /**
     * @param  array{utm_source?: ?string, utm_medium?: ?string, utm_campaign?: ?string, utm_term?: ?string, utm_content?: ?string, gclid?: ?string, fbclid?: ?string, referrer?: ?string}  $touch
     */
    public function classify(array $touch): Channel
    {
        $source = Str::lower(trim((string) ($touch['utm_source'] ?? '')));
        $medium = Str::lower(trim((string) ($touch['utm_medium'] ?? '')));
        $host = self::host((string) ($touch['referrer'] ?? ''));

        $hasUtm = $source !== '' || $medium !== ''
            || filled($touch['utm_campaign'] ?? null)
            || filled($touch['utm_term'] ?? null)
            || filled($touch['utm_content'] ?? null);

        $paidMedium = $this->mediumIn($medium, $this->paidMediums);
        $socialSource = $this->sourceIn($source, $this->socialSources);
        $socialMedium = $this->mediumIn($medium, $this->socialMediums);

        if ($paidMedium && ($socialSource || $socialMedium)) {
            return Channel::PaidSocial;
        }

        if (filled($touch['gclid'] ?? null) || $paidMedium) {
            return Channel::PaidSearch;
        }

        if (filled($touch['fbclid'] ?? null)) {
            return Channel::PaidSocial;
        }

        if ($this->mediumIn($medium, $this->emailMediums) || $this->mediumIn($source, $this->emailMediums)) {
            return Channel::Email;
        }

        if ($medium === 'organic' || $this->sourceIn($source, $this->searchSources) || $this->sourceIn($host, $this->searchSources)) {
            return Channel::OrganicSearch;
        }

        if ($socialMedium || $socialSource || $this->sourceIn($host, $this->socialSources)) {
            return Channel::OrganicSocial;
        }

        if ($medium === 'referral' || (! $hasUtm && $host !== '')) {
            return Channel::Referral;
        }

        return $hasUtm ? Channel::Unassigned : Channel::Direct;
    }

    /**
     * The lower-cased host of a URL, without "www.". Empty when it has none.
     */
    public static function host(string $url): string
    {
        $host = parse_url(trim($url), PHP_URL_HOST);

        return is_string($host) ? Str::lower(Str::after($host, 'www.')) : '';
    }

    /**
     * @param  list<string>  $fallback
     * @return list<string>
     */
    protected static function listOr(?string $value, array $fallback): array
    {
        $list = self::parseList($value);

        return $list !== [] ? $list : $fallback;
    }

    /**
     * Whether a source or host matches an entry on a whole label or suffix.
     *
     * @param  list<string>  $entries
     */
    protected function sourceIn(string $value, array $entries): bool
    {
        if ($value === '') {
            return false;
        }

        $labels = preg_split('/[.\s_\-\/]+/', $value) ?: [];

        foreach ($entries as $entry) {
            if (str_contains($entry, '.')) {
                if ($value === $entry || str_ends_with($value, '.'.$entry)) {
                    return true;
                }

                continue;
            }

            if (in_array($entry, $labels, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $patterns
     */
    protected function mediumIn(string $medium, array $patterns): bool
    {
        if ($medium === '') {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $medium)) {
                return true;
            }
        }

        return false;
    }
}
