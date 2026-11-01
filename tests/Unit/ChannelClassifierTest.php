<?php

namespace Tests\Unit;

use App\Services\Enquiries\Channel;
use App\Services\Enquiries\ChannelClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The GA4-style channel rules an enquiry's first touch is classified by.
 */
class ChannelClassifierTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, string>, 1: Channel}>
     */
    public static function touches(): array
    {
        return [
            'gclid alone' => [['gclid' => 'abc'], Channel::PaidSearch],
            'gclid with google cpc tags' => [['gclid' => 'abc', 'utm_source' => 'google', 'utm_medium' => 'cpc', 'utm_campaign' => 'winter'], Channel::PaidSearch],
            'utm_medium cpc' => [['utm_source' => 'bing', 'utm_medium' => 'cpc'], Channel::PaidSearch],
            'utm_medium ppc' => [['utm_medium' => 'ppc'], Channel::PaidSearch],
            'utm_medium paid' => [['utm_source' => 'google', 'utm_medium' => 'paid'], Channel::PaidSearch],
            'medium is case-insensitive' => [['utm_medium' => 'CPC'], Channel::PaidSearch],
            'fbclid alone' => [['fbclid' => 'xyz', 'referrer' => 'https://l.facebook.com/'], Channel::PaidSocial],
            'paid medium from facebook' => [['utm_source' => 'facebook', 'utm_medium' => 'cpc'], Channel::PaidSocial],
            'paid medium from instagram' => [['utm_source' => 'instagram', 'utm_medium' => 'paid'], Channel::PaidSocial],
            'paid medium from linkedin' => [['utm_source' => 'linkedin', 'utm_medium' => 'ppc'], Channel::PaidSocial],
            'paid_social medium from any source' => [['utm_source' => 'meta_ads', 'utm_medium' => 'paid_social'], Channel::PaidSocial],
            'google referrer' => [['referrer' => 'https://www.google.co.za/'], Channel::OrganicSearch],
            'bing referrer' => [['referrer' => 'https://www.bing.com/search?q=hfc'], Channel::OrganicSearch],
            'yahoo referrer' => [['referrer' => 'https://za.search.yahoo.com/'], Channel::OrganicSearch],
            'duckduckgo referrer' => [['referrer' => 'https://duckduckgo.com/'], Channel::OrganicSearch],
            'google android app referrer' => [['referrer' => 'android-app://com.google.android.googlequicksearchbox/'], Channel::OrganicSearch],
            'utm_medium organic' => [['utm_source' => 'google', 'utm_medium' => 'organic'], Channel::OrganicSearch],
            'facebook referrer' => [['referrer' => 'https://l.facebook.com/'], Channel::OrganicSocial],
            'instagram referrer' => [['referrer' => 'https://www.instagram.com/'], Channel::OrganicSocial],
            'linkedin referrer' => [['referrer' => 'https://www.linkedin.com/feed/'], Channel::OrganicSocial],
            'tiktok referrer' => [['referrer' => 'https://www.tiktok.com/'], Channel::OrganicSocial],
            'x referrer' => [['referrer' => 'https://x.com/hfc'], Channel::OrganicSocial],
            't.co referrer' => [['referrer' => 'https://t.co/abc'], Channel::OrganicSocial],
            'social medium' => [['utm_source' => 'hfc_page', 'utm_medium' => 'social'], Channel::OrganicSocial],
            'facebook source, no medium' => [['utm_source' => 'facebook'], Channel::OrganicSocial],
            'email medium' => [['utm_source' => 'mailchimp', 'utm_medium' => 'email'], Channel::Email],
            'newsletter source' => [['utm_source' => 'newsletter', 'utm_campaign' => 'oct'], Channel::Email],
            'other referrer' => [['referrer' => 'https://www.property24.com/listing/1'], Channel::Referral],
            'referral medium' => [['utm_source' => 'partner', 'utm_medium' => 'referral'], Channel::Referral],
            'nothing at all' => [[], Channel::Direct],
            'blank values' => [['utm_source' => ' ', 'referrer' => ''], Channel::Direct],
            'unrecognised utm tags' => [['utm_source' => 'flyer', 'utm_medium' => 'print'], Channel::Unassigned],
            'campaign only' => [['utm_campaign' => 'spring'], Channel::Unassigned],
            'a host merely containing a search name is not search' => [['referrer' => 'https://googleblog-fan.com/'], Channel::Referral],
            'a host merely containing x is not social' => [['referrer' => 'https://example.com/'], Channel::Referral],
        ];
    }

    /**
     * @param  array<string, string>  $touch
     */
    #[DataProvider('touches')]
    public function test_it_classifies_a_first_touch(array $touch, Channel $expected): void
    {
        $this->assertSame($expected, (new ChannelClassifier)->classify($touch));
    }

    public function test_the_lists_are_configurable(): void
    {
        $classifier = new ChannelClassifier(
            searchSources: ['seznam'],
            socialSources: ['mastodon.social'],
            paidMediums: ['boosted'],
        );

        $this->assertSame(Channel::OrganicSearch, $classifier->classify(['referrer' => 'https://search.seznam.cz/']));
        $this->assertSame(Channel::OrganicSocial, $classifier->classify(['referrer' => 'https://mastodon.social/@hfc']));
        $this->assertSame(Channel::PaidSearch, $classifier->classify(['utm_medium' => 'boosted']));
        // Google is no longer in the search list, so it is just a referrer.
        $this->assertSame(Channel::Referral, $classifier->classify(['referrer' => 'https://www.google.com/']));
        // cpc is no longer a paid medium.
        $this->assertSame(Channel::Unassigned, $classifier->classify(['utm_medium' => 'cpc']));
    }

    public function test_it_parses_a_one_per_line_setting(): void
    {
        $this->assertSame(['google', 'bing', 'yahoo'], ChannelClassifier::parseList("Google\r\n  bing \n\n,yahoo\ngoogle"));
        $this->assertSame([], ChannelClassifier::parseList(null));
    }
}
