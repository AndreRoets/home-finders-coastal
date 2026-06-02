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
}
