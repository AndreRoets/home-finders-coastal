<?php

namespace App\Services\Corex;

/**
 * Deterministic demo dataset shaped exactly like the raw CoreX website API,
 * so it flows through {@see ListingMapper} / {@see AgentMapper} and the
 * controller filters unchanged. Used locally (COREX_DEMO=true) to populate the
 * site while the live CoreX feed is unavailable.
 *
 * Everything is derived from the row index — no randomness — so a listing's
 * detail page always matches its card across requests.
 */
class DemoData
{
    private const LISTING_COUNT = 50;

    private const AGENT_COUNT = 10;

    private const TESTIMONIAL_COUNT = 12;

    /** @var list<string> Coastal suburbs paired with their city below. */
    private const SUBURBS = [
        ['Camps Bay', 'Cape Town', 'Western Cape'],
        ['Clifton', 'Cape Town', 'Western Cape'],
        ['Bantry Bay', 'Cape Town', 'Western Cape'],
        ['Sea Point', 'Cape Town', 'Western Cape'],
        ['Llandudno', 'Cape Town', 'Western Cape'],
        ['Hout Bay', 'Cape Town', 'Western Cape'],
        ['Kommetjie', 'Cape Town', 'Western Cape'],
        ['Noordhoek', 'Cape Town', 'Western Cape'],
        ['Muizenberg', 'Cape Town', 'Western Cape'],
        ["Simon's Town", 'Cape Town', 'Western Cape'],
        ['Bloubergstrand', 'Cape Town', 'Western Cape'],
        ['Gordon\'s Bay', 'Cape Town', 'Western Cape'],
        ['Hermanus', 'Overberg', 'Western Cape'],
        ['Plettenberg Bay', 'Garden Route', 'Western Cape'],
        ['Knysna', 'Garden Route', 'Western Cape'],
        ['Mossel Bay', 'Garden Route', 'Western Cape'],
        ['Umhlanga', 'Durban', 'KwaZulu-Natal'],
        ['Ballito', 'Dolphin Coast', 'KwaZulu-Natal'],
        ['Salt Rock', 'Dolphin Coast', 'KwaZulu-Natal'],
        ['Jeffreys Bay', 'Kouga', 'Eastern Cape'],
    ];

    private const PROPERTY_TYPES = ['Apartment', 'House', 'Villa', 'Penthouse', 'Townhouse', 'Cottage', 'Estate'];

    /** @var list<string> Lead-in adjectives for titles. */
    private const DESCRIPTORS = [
        'Oceanfront', 'Clifftop', 'Modern', 'Architect-designed', 'Sun-drenched',
        'Secluded', 'Beachfront', 'Elegant', 'Contemporary', 'Coastal',
    ];

    /** @var list<string> Unsplash photo IDs used to build galleries. */
    private const IMAGES = [
        'photo-1613490493576-7fde63acd811',
        'photo-1512917774080-9991f1c4c750',
        'photo-1570129477492-45c003edd2be',
        'photo-1568605114967-8130f3a36994',
        'photo-1502672260266-1c1ef2d93688',
        'photo-1493809842364-78817add7ffb',
        'photo-1600596542815-ffad4c1539a9',
        'photo-1600585154340-be6161a56a0c',
        'photo-1600607687939-ce8a6c25118c',
        'photo-1605276374104-dee2a0ed3cd6',
        'photo-1512915922686-57c11dde9b6b',
        'photo-1449844908441-8829872d2607',
        'photo-1583608205776-bfd35f0d9f83',
        'photo-1580587771525-78b9dba3b914',
        'photo-1564013799919-ab600027ffc6',
        'photo-1576941089067-2de3c901e126',
    ];

    /** @var list<string> Feature chips. */
    private const FEATURES = [
        'Sea views', 'Swimming pool', 'Open-plan living', 'North-facing', 'Double garage',
        'Built-in braai', 'Air conditioning', 'Solar power & inverter', 'Borehole', 'Wine cellar',
        'Staff accommodation', 'Smart-home system', 'Underfloor heating', 'Fibre ready',
        'Walk to the beach', 'Landscaped garden', 'Granite countertops', 'Scullery & laundry',
        '24-hour security', 'Entertainer\'s patio',
    ];

    /** @var list<array{string, string}> Agent first/last names. */
    private const AGENT_NAMES = [
        ['Thandi', 'Mbeki'], ['Daniel', 'van der Merwe'], ['Aisha', 'Patel'], ['Sipho', 'Dlamini'],
        ['Megan', 'Roberts'], ['Johan', 'Pretorius'], ['Nomvula', 'Khumalo'], ['Liam', "O'Connor"],
        ['Zanele', 'Nkosi'], ['Chloe', 'Bezuidenhout'],
    ];

    /**
     * Agent designations, aligned by index with AGENT_NAMES. A couple are null
     * to mirror CoreX (designation is optional) and exercise the hide-when-empty
     * path on the cards.
     *
     * @var list<string|null>
     */
    private const AGENT_DESIGNATIONS = [
        'Principal Property Practitioner', 'Candidate Property Practitioner', 'Property Practitioner', null,
        'Property Practitioner', 'Candidate Property Practitioner', 'Principal Property Practitioner', 'Property Practitioner',
        null, 'Candidate Property Practitioner',
    ];

    /**
     * All demo listings in raw CoreX shape.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listings(): array
    {
        $listings = [];

        for ($i = 1; $i <= self::LISTING_COUNT; $i++) {
            $listings[] = self::makeListing($i);
        }

        return $listings;
    }

    /**
     * Find a single listing by numeric id or reference (e.g. "HFC1007").
     *
     * @return array<string, mixed>
     */
    public static function findListing(int|string $idOrRef): array
    {
        foreach (self::listings() as $listing) {
            if ((string) $listing['id'] === (string) $idOrRef
                || strcasecmp((string) $listing['reference'], (string) $idOrRef) === 0) {
                return $listing;
            }
        }

        return [];
    }

    /**
     * All demo agents in raw CoreX shape.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function agents(): array
    {
        $agents = [];

        for ($i = 1; $i <= self::AGENT_COUNT; $i++) {
            $agents[] = self::makeAgent($i);
        }

        return $agents;
    }

    /**
     * Find a single agent by id.
     *
     * @return array<string, mixed>
     */
    public static function findAgent(int|string $id): array
    {
        foreach (self::agents() as $agent) {
            if ((string) $agent['id'] === (string) $id) {
                return $agent;
            }
        }

        return [];
    }

    /**
     * All demo testimonials in raw CoreX shape.
     *
     * @param  array<string, mixed>  $query  Supports an `agent_id` filter.
     * @return array<int, array<string, mixed>>
     */
    public static function testimonials(array $query = []): array
    {
        $testimonials = [];

        for ($i = 1; $i <= self::TESTIMONIAL_COUNT; $i++) {
            $testimonials[] = self::makeTestimonial($i);
        }

        if (isset($query['agent_id'])) {
            $agentId = (string) $query['agent_id'];

            $testimonials = array_values(array_filter(
                $testimonials,
                static fn (array $t): bool => (string) ($t['agent_id'] ?? '') === $agentId,
            ));
        }

        return $testimonials;
    }

    /**
     * Find a single testimonial by id.
     *
     * @return array<string, mixed>
     */
    public static function findTestimonial(int|string $id): array
    {
        foreach (self::testimonials() as $testimonial) {
            if ((string) $testimonial['id'] === (string) $id) {
                return $testimonial;
            }
        }

        return [];
    }

    /** @var list<string> */
    private const TESTIMONIAL_AUTHORS = [
        'Helena & Marcus Bauer', 'Pieter van Niekerk', 'Aisha Daniels', 'The Ndlovu Family',
        'Sarah Whitfield', 'James & Rebecca Cole', 'Nomsa Khanyile', 'David Abrahams',
        'Carla Esterhuizen', 'Mohammed & Fatima Patel', 'Grace Oosthuizen', 'The Reddy Family',
    ];

    /** @var list<string> */
    private const TESTIMONIAL_BODIES = [
        'They understood exactly the kind of home we were after and found it before it ever hit the market. The whole sale was handled with real discretion.',
        'Our clifftop home sold within three weeks at a price we did not think possible. Two decades on this coastline clearly counts for something.',
        'Calm, considered and always a step ahead. From the first viewing to the keys, it felt like we had the whole coast working for us.',
        'Professional from start to finish. Every question was answered honestly and we never felt pressured into a decision.',
        'We were nervous first-time buyers and they walked us through every step with patience. Could not have asked for better guidance.',
        'The marketing of our property was exceptional — the photography alone brought buyers through the door within days.',
        'Honest, hard-working and genuinely invested in getting us the right outcome. We have already recommended them to friends.',
        'Sold our home and helped us find the next one in the same month. Seamless, stress-free and handled with real care.',
        'Their local knowledge is unmatched. They knew the street, the neighbours and exactly what our home was worth.',
        'A truly personal service. We always felt like their only client, even when we knew how busy they were.',
        'They negotiated firmly on our behalf and secured a price well above what we expected. Worth every cent.',
        'From the first call to the final signature, everything was handled with warmth and absolute professionalism.',
    ];

    /**
     * @return array<string, mixed>
     */
    private static function makeTestimonial(int $i): array
    {
        // Most testimonials are tied to an agent; a couple are left unattributed
        // (agent_id null) to exercise the "no agent" rendering path. One rating
        // is left null to exercise the "hide stars" path.
        $hasAgent = $i % 6 !== 0;
        $agent = $hasAgent ? self::makeAgent((($i - 1) % self::AGENT_COUNT) + 1) : null;

        return [
            'id' => 500 + $i,
            'author' => self::TESTIMONIAL_AUTHORS[($i - 1) % count(self::TESTIMONIAL_AUTHORS)],
            'rating' => $i % 7 === 0 ? null : 5 - ($i % 2),
            'body' => self::TESTIMONIAL_BODIES[($i - 1) % count(self::TESTIMONIAL_BODIES)],
            'date' => date('Y-m-d', strtotime('-'.($i * 9).' days')),
            'agent_id' => $agent['id'] ?? null,
            'agent' => $agent !== null ? ['id' => $agent['id'], 'name' => $agent['name']] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function makeListing(int $i): array
    {
        [$suburb, $city, $province] = self::SUBURBS[($i - 1) % count(self::SUBURBS)];
        $type = self::PROPERTY_TYPES[$i % count(self::PROPERTY_TYPES)];
        $descriptor = self::DESCRIPTORS[$i % count(self::DESCRIPTORS)];

        // Bucket the listing: ~10 rentals, ~7 sold, the rest for sale; every
        // third for-sale listing is a sole mandate (HFC Exclusive).
        $isRental = $i % 5 === 0;
        $isSold = ! $isRental && $i % 7 === 0;
        $isExclusive = ! $isRental && ! $isSold && $i % 3 === 0;

        $beds = 1 + ($i % 6);
        $baths = 1 + ($i % 5);
        $garages = $i % 4;
        $size = 70 + (($i * 37) % 760);
        $erf = in_array($type, ['House', 'Villa', 'Estate', 'Townhouse'], true)
            ? $size + 200 + (($i * 53) % 900)
            : null;

        $listing = [
            'id' => 1000 + $i,
            'reference' => 'HFC'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            // Higher index = more recently listed (today back to ~7 weeks ago).
            'listed_at' => date('Y-m-d', strtotime('-'.(self::LISTING_COUNT - $i).' days')),
            'title' => "{$descriptor} {$type} in {$suburb}",
            'headline' => self::headline($descriptor, $type, $suburb),
            'description' => self::description($descriptor, $type, $suburb, $beds),
            'property_type' => $type,
            'category' => 'Residential',
            'suburb' => $suburb,
            'city' => $city,
            'province' => $province,
            'street_number' => (string) (1 + (($i * 7) % 180)),
            'street_name' => self::STREET_NAMES[$i % count(self::STREET_NAMES)],
            'beds' => $beds,
            'baths' => $baths,
            'garages' => $garages,
            'size_m2' => $size,
            'erf_size_m2' => $erf,
            'pet_friendly' => $i % 2 === 0,
            'features' => self::features($i),
            'images' => self::images($i),
            'agent' => self::makeAgent((($i - 1) % self::AGENT_COUNT) + 1),
            'costs' => [
                'rates_taxes' => 1500 + (($i * 90) % 6000),
                'levy' => in_array($type, ['Apartment', 'Penthouse', 'Townhouse'], true)
                    ? 1200 + (($i * 70) % 4500)
                    : null,
                'special_levy' => null,
            ],
        ];

        if (in_array($type, ['Apartment', 'Penthouse'], true)) {
            $listing['complex_name'] = self::COMPLEX_NAMES[$i % count(self::COMPLEX_NAMES)];
        }

        if ($isRental) {
            $listing['listing_type'] = 'rental';
            $listing['status'] = 'to_let';
            $listing['rental'] = [
                'rental_amount' => 12000 + (($i * 2300) % 85000),
                'rental_price_type' => 'per_month',
            ];
        } else {
            $listing['listing_type'] = 'sale';
            $listing['status'] = $isSold ? 'sold' : 'for_sale';
            $listing['price'] = 3_500_000 + (($i * 1_750_000) % 72_000_000);
        }

        if ($isExclusive) {
            $listing['mandate_type'] = 'sole';
        }

        return $listing;
    }

    /** @var list<string> */
    private const STREET_NAMES = [
        'Beach Road', 'Marine Drive', 'Victoria Road', 'The Ridge', 'Clifton Steps',
        'Ocean View Drive', 'Camps Bay Drive', 'Geneva Drive', 'Fisherman\'s Bend', 'Lagoon Way',
    ];

    /** @var list<string> */
    private const COMPLEX_NAMES = [
        'The Aurora', 'Atlantic Reach', 'Bay Reflections', 'The Promenade', 'Sea Glass',
        'Horizon Residences', 'Tide & Stone', 'The Esplanade',
    ];

    private static function headline(string $descriptor, string $type, string $suburb): string
    {
        return "{$descriptor} {$type} with uninterrupted views in the heart of {$suburb}.";
    }

    private static function description(string $descriptor, string $type, string $suburb, int $beds): string
    {
        return "This {$descriptor} {$type} in sought-after {$suburb} pairs considered design with "
            ."effortless coastal living. {$beds} generous bedrooms open onto light-filled living "
            ."spaces that flow to an entertainer's terrace framing the sea.\n\n"
            .'Walls of glass draw in the light and the horizon, while crafted finishes and a seamless '
            .'indoor-outdoor layout make this a rare offering on this stretch of coastline. A private '
            .'viewing is highly recommended.';
    }

    /**
     * @return list<string>
     */
    private static function features(int $i): array
    {
        $count = 6 + ($i % 3);
        $features = [];

        for ($k = 0; $k < $count; $k++) {
            $features[] = self::FEATURES[($i + $k * 3) % count(self::FEATURES)];
        }

        return array_values(array_unique($features));
    }

    /**
     * @return list<string>
     */
    private static function images(int $i): array
    {
        $images = [];

        for ($k = 0; $k < 5; $k++) {
            $id = self::IMAGES[($i + $k) % count(self::IMAGES)];
            $images[] = "https://images.unsplash.com/{$id}?auto=format&fit=crop&w=1200&q=70";
        }

        return $images;
    }

    /**
     * @return array<string, mixed>
     */
    private static function makeAgent(int $i): array
    {
        [$first, $last] = self::AGENT_NAMES[($i - 1) % count(self::AGENT_NAMES)];
        $slug = strtolower($first.'.'.preg_replace('/[^a-z]/i', '', $last));

        return [
            'id' => 20 + $i,
            'name' => "{$first} {$last}",
            'designation' => self::AGENT_DESIGNATIONS[($i - 1) % count(self::AGENT_DESIGNATIONS)],
            'email' => "{$slug}@homefinderscoastal.com",
            'cell' => sprintf('+27 8%d %03d %04d', $i % 5 + 1, ($i * 137) % 1000, ($i * 911) % 10000),
            'photo_url' => "https://i.pravatar.cc/600?img={$i}",
        ];
    }
}
