<?php

namespace Tests\Unit;

use App\Support\DynamicSeo;
use App\Support\StructuredData;
use PHPUnit\Framework\TestCase;

/**
 * The pure helpers behind the cleaner search snippets and the agency schema:
 * listing text clean-up, E.164 phone numbers and schema.org opening hours.
 */
class SeoTextAndSchemaHelpersTest extends TestCase
{
    public function test_listing_text_loses_broken_emoji_boilerplate_and_shouting(): void
    {
        $clean = DynamicSeo::cleanListingText(
            '?? FULLY FURNISHED FAMILY HOME TO RENT IN UVONGO ?? Authority to Rent Spacious house with `a` garden',
        );

        $this->assertSame("Fully Furnished Family Home To Rent In Uvongo — Spacious house with 'a' garden", $clean);
    }

    public function test_listing_text_keeps_known_acronyms_and_strips_mandate_phrases(): void
    {
        $this->assertSame(
            'Invest in this KZN South Coast apartment.',
            DynamicSeo::cleanListingText('Exclusive Authority To Sell. Invest in this KZN SOUTH COAST apartment.'),
        );
    }

    public function test_listing_text_with_nothing_readable_is_empty(): void
    {
        $this->assertSame('', DynamicSeo::cleanListingText('?? ??'));
        $this->assertSame('Lovely home & garden', DynamicSeo::cleanListingText('<p>Lovely home &amp; garden</p>'));
    }

    public function test_south_african_numbers_become_e164(): void
    {
        $this->assertSame('+27713510291', StructuredData::internationalPhone('071 351 0291'));
        $this->assertSame('+27713510291', StructuredData::internationalPhone('+27 71 351 0291'));
        $this->assertSame('+27713510291', StructuredData::internationalPhone('27713510291'));
        $this->assertNull(StructuredData::internationalPhone(''));
        $this->assertNull(StructuredData::internationalPhone(null));
    }

    public function test_opening_hours_become_schema_org_strings(): void
    {
        $this->assertSame(
            ['Mo-Fr 08:00-17:00', 'Sa 09:00-13:00'],
            StructuredData::openingHours([
                ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                ['days' => 'Saturday', 'hours' => '9:00 - 13:00'],
            ]),
        );
    }

    public function test_unreadable_or_closed_opening_hours_are_dropped_not_guessed(): void
    {
        $this->assertSame([], StructuredData::openingHours([
            ['days' => 'Sunday', 'hours' => 'Closed'],
            ['days' => 'Weekends', 'hours' => '09:00 - 12:00'],
        ]));
    }
}
