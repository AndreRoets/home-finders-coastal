<?php

namespace Tests\Unit;

use App\Services\Corex\AgencyMapper;
use PHPUnit\Framework\TestCase;

/**
 * The agency mapper turns the raw CoreX agency resource into the shape the
 * shared footer/header consume. Its core contract is the golden rule: anything
 * CoreX does not set must be absent from the output, never an empty placeholder.
 */
class CorexAgencyMapperTest extends TestCase
{
    public function test_it_maps_a_full_agency_payload(): void
    {
        $mapped = AgencyMapper::map([
            'data' => [
                'name' => 'Home Finders Coastal',
                'trading_name' => 'HFC — The Mandate Company',
                'logo_url' => 'https://corex.test/logo.png',
                'branding' => [
                    'sidebar_color' => '#0b1f33',
                    'icon_color' => '#1f7a8c',
                    'default_color' => '#0b1f33',
                    'button_color' => '#1f7a8c',
                ],
                'contact' => [
                    'email' => 'info@example.co.za',
                    'phone' => '039 000 0000',
                    'address' => '12 Marina Drive, Uvongo, 4275',
                ],
                'social' => [
                    'facebook' => 'homefinderscoastal', // bare handle
                    'instagram' => 'https://instagram.com/hfc', // full URL passes through
                    'linkedin' => 'hfc-coastal',
                    'youtube' => 'hfcoastal',
                ],
                'open_hours' => [
                    ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                    ['days' => 'Saturday', 'hours' => '09:00 – 13:00'],
                ],
            ],
        ]);

        $this->assertSame('Home Finders Coastal', $mapped['name']);
        $this->assertSame('HFC — The Mandate Company', $mapped['tradingName']);
        $this->assertSame('https://corex.test/logo.png', $mapped['logo']);

        // Branding keys are camelCased.
        $this->assertSame('#1f7a8c', $mapped['branding']['iconColor']);
        $this->assertSame('#0b1f33', $mapped['branding']['defaultColor']);

        // Contact: phone keeps its display value and gains a stripped href.
        $this->assertSame('039 000 0000', $mapped['contact']['phone']);
        $this->assertSame('0390000000', $mapped['contact']['phoneHref']);
        $this->assertSame('info@example.co.za', $mapped['contact']['email']);

        // Socials: bare handles expand to full URLs, full URLs pass through.
        $this->assertSame('https://facebook.com/homefinderscoastal', $mapped['socials']['facebook']);
        $this->assertSame('https://instagram.com/hfc', $mapped['socials']['instagram']);
        $this->assertSame('https://linkedin.com/hfc-coastal', $mapped['socials']['linkedin']);
        $this->assertSame('https://youtube.com/@hfcoastal', $mapped['socials']['youtube']);

        $this->assertCount(2, $mapped['openHours']);
        $this->assertSame(['days' => 'Saturday', 'hours' => '09:00 – 13:00'], $mapped['openHours'][1]);
    }

    public function test_it_returns_null_when_the_agency_is_empty(): void
    {
        $this->assertNull(AgencyMapper::map([]));
        $this->assertNull(AgencyMapper::map(['data' => []]));
    }

    public function test_it_omits_absent_sections_and_keys(): void
    {
        // Only an email is set — no phone, no address, no socials, no hours.
        $mapped = AgencyMapper::map([
            'name' => 'Sparse Agency',
            'contact' => ['email' => 'only@example.co.za'],
        ]);

        $this->assertSame(['email' => 'only@example.co.za'], $mapped['contact']);
        $this->assertArrayNotHasKey('phone', $mapped['contact']);
        $this->assertArrayNotHasKey('phoneHref', $mapped['contact']);
        $this->assertArrayNotHasKey('address', $mapped['contact']);

        // Whole sections collapse so the frontend hides them entirely.
        $this->assertNull($mapped['branding']);
        $this->assertSame([], $mapped['socials']);
        $this->assertSame([], $mapped['openHours']);
        $this->assertNull($mapped['tradingName']);
        $this->assertNull($mapped['logo']);
    }

    public function test_contact_is_null_when_every_detail_is_blank(): void
    {
        $mapped = AgencyMapper::map([
            'name' => 'Blank Contact',
            'contact' => ['email' => '', 'phone' => '   '],
        ]);

        $this->assertNull($mapped['contact']);
    }

    public function test_open_hours_drops_incomplete_rows_and_keeps_closed(): void
    {
        $mapped = AgencyMapper::map([
            'name' => 'Hours Agency',
            'open_hours' => [
                ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                ['days' => 'Sunday', 'hours' => 'Closed'],
                ['days' => 'Missing hours'], // dropped
                ['hours' => '10:00'], // dropped
            ],
        ]);

        $this->assertCount(2, $mapped['openHours']);
        $this->assertSame(['days' => 'Sunday', 'hours' => 'Closed'], $mapped['openHours'][1]);
    }
}
