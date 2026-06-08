<?php

namespace Tests\Unit;

use App\Services\Corex\ListingMapper;
use PHPUnit\Framework\TestCase;

class CorexListingMapperTest extends TestCase
{
    public function test_it_maps_a_sale_listing(): void
    {
        $mapped = ListingMapper::map([
            'id' => 101,
            'reference' => 'abc-uuid',
            'title' => 'Sea-View Family Home',
            'suburb' => 'Uvongo',
            'town' => 'Margate',
            'city' => 'Margate',
            'province' => 'KwaZulu-Natal',
            'listing_type' => 'sale',
            'status' => 'active',
            'price' => 2495000,
            'price_display' => 'R 2,495,000',
            'price_on_application' => false,
            'beds' => 3,
            'baths' => 2.5,
            'size_m2' => 220,
            'images' => ['https://corex.test/1.jpg', 'https://corex.test/2.jpg'],
        ]);

        $this->assertSame(101, $mapped['id']);
        $this->assertSame('abc-uuid', $mapped['ref']);
        $this->assertSame('Sea-View Family Home', $mapped['title']);
        $this->assertSame('Uvongo, Margate', $mapped['location']);
        $this->assertSame('R 2,495,000', $mapped['price']);
        $this->assertSame(3, $mapped['beds']);
        $this->assertSame(3, $mapped['baths']); // 2.5 rounded for the badge
        $this->assertSame('220 m²', $mapped['area']);
        $this->assertSame('for-sale', $mapped['status']);
        $this->assertSame('https://corex.test/1.jpg', $mapped['image']);
    }

    public function test_it_maps_a_rental_using_the_rental_object(): void
    {
        $mapped = ListingMapper::map([
            'listing_type' => 'rental',
            'status' => 'active',
            'price_display' => 'R 0',
            'rental' => [
                'rental_amount' => 12500,
                'rental_price_type' => 'per_month',
            ],
        ]);

        $this->assertSame('to-rent', $mapped['status']);
        $this->assertSame('R 12 500 / month', $mapped['price']);
    }

    public function test_sold_status_wins_over_listing_type(): void
    {
        $mapped = ListingMapper::map([
            'listing_type' => 'sale',
            'status' => 'sold',
        ]);

        $this->assertSame('sold', $mapped['status']);
    }

    public function test_force_status_overrides_for_exclusive_page(): void
    {
        $mapped = ListingMapper::map(['listing_type' => 'sale', 'status' => 'active'], 'exclusive');

        $this->assertSame('exclusive', $mapped['status']);
    }

    public function test_price_on_application(): void
    {
        $mapped = ListingMapper::map([
            'price' => 2495000,
            'price_on_application' => true,
        ]);

        $this->assertSame('Price on application', $mapped['price']);
    }

    public function test_it_falls_back_to_safe_defaults(): void
    {
        $mapped = ListingMapper::map([]);

        $this->assertSame('Untitled listing', $mapped['title']);
        $this->assertSame('Price on application', $mapped['price']);
        $this->assertSame(0, $mapped['beds']);
        $this->assertSame('—', $mapped['area']);
        $this->assertSame('for-sale', $mapped['status']);
        $this->assertStringContainsString('placehold', $mapped['image']);
    }

    public function test_it_maps_multiple_agents_on_a_listing(): void
    {
        $listing = [
            'id' => 202,
            // `agent` (singular) still resolves to the primary for back-compat.
            'agent' => ['id' => 7, 'name' => 'Lerato Mokoena', 'designation' => 'Principal', 'photo_url' => 'https://corex.test/7.jpg', 'is_primary' => true],
            // CoreX carries every attributed agent in `agents`, primary first.
            'agents' => [
                ['id' => 7, 'name' => 'Lerato Mokoena', 'designation' => 'Principal', 'photo_url' => 'https://corex.test/7.jpg', 'is_primary' => true],
                ['id' => 9, 'name' => 'Sipho Dlamini', 'photo_url' => 'https://corex.test/9.jpg', 'is_primary' => false],
            ],
        ];

        $card = ListingMapper::map($listing);

        // Deduped, primary first; `agent` stays the primary for back-compat.
        $this->assertCount(2, $card['agents']);
        $this->assertSame(7, $card['agents'][0]['id']);
        $this->assertSame(9, $card['agents'][1]['id']);
        $this->assertSame($card['agents'][0], $card['agent']);

        // Detail carries the full agent shape (phone/email).
        $detail = ListingMapper::detail($listing);
        $this->assertCount(2, $detail['agents']);
        $this->assertSame('Sipho Dlamini', $detail['agents'][1]['name']);
        $this->assertArrayHasKey('email', $detail['agents'][1]);
    }

    public function test_the_primary_agent_leads_regardless_of_array_order(): void
    {
        // Even if CoreX returns the co-agent first, is_primary floats the
        // primary to the front so the detail/card always lead with it.
        $mapped = ListingMapper::map([
            'id' => 206,
            'agents' => [
                ['id' => 9, 'name' => 'Sipho Dlamini', 'photo_url' => 'https://corex.test/9.jpg', 'is_primary' => false],
                ['id' => 7, 'name' => 'Lerato Mokoena', 'photo_url' => 'https://corex.test/7.jpg', 'is_primary' => true],
            ],
        ]);

        $this->assertSame(7, $mapped['agents'][0]['id']);
        $this->assertSame(9, $mapped['agents'][1]['id']);
        $this->assertSame(7, $mapped['agent']['id']);
    }

    public function test_a_single_agent_listing_yields_one_agent(): void
    {
        $mapped = ListingMapper::map([
            'id' => 303,
            'agent' => ['id' => 4, 'name' => 'Thandi Nkosi', 'photo_url' => 'https://corex.test/4.jpg'],
        ]);

        $this->assertCount(1, $mapped['agents']);
        $this->assertSame(4, $mapped['agent']['id']);
    }

    public function test_a_listing_with_no_agent_yields_an_empty_agents_list(): void
    {
        $mapped = ListingMapper::map(['id' => 404]);

        $this->assertSame([], $mapped['agents']);
        $this->assertNull($mapped['agent']);
    }

    public function test_it_builds_a_title_based_slug_ending_in_the_id(): void
    {
        $mapped = ListingMapper::map([
            'id' => 12345,
            'title' => 'Apartment For Sale in Margate, KwaZulu Natal',
        ]);

        $this->assertSame(
            'apartment-for-sale-in-margate-kwazulu-natal-12345',
            $mapped['slug'],
        );
    }

    public function test_slug_falls_back_to_the_bare_id_without_a_title(): void
    {
        $this->assertSame('77', ListingMapper::slug(['id' => 77]));
    }

    public function test_id_from_slug_reads_the_trailing_id(): void
    {
        $this->assertSame('12345', ListingMapper::idFromSlug('apartment-for-sale-in-margate-kwazulu-natal-12345'));
    }

    public function test_id_from_slug_passes_through_a_bare_id_or_reference(): void
    {
        $this->assertSame('101', ListingMapper::idFromSlug('101'));
        $this->assertSame('abc-uuid', ListingMapper::idFromSlug('abc-uuid'));
    }

    public function test_it_collapses_doubled_storage_segment_in_image_urls(): void
    {
        $listing = [
            'id' => 531,
            'images' => [
                'https://staging.corexos.co.za/storage/storage/properties/531/a.jpg',
                'https://staging.corexos.co.za/storage/properties/531/b.jpg',
            ],
        ];

        $card = ListingMapper::map($listing);
        $this->assertSame(
            'https://staging.corexos.co.za/storage/properties/531/a.jpg',
            $card['image'],
        );

        $detail = ListingMapper::detail($listing);
        $this->assertSame([
            'https://staging.corexos.co.za/storage/properties/531/a.jpg',
            'https://staging.corexos.co.za/storage/properties/531/b.jpg',
        ], $detail['images']);
    }
}
