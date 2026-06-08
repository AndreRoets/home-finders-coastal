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

    private const ARTICLE_COUNT = 9;

    private const BRANCH_COUNT = 3;

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
     * Static branch (office) definitions in raw CoreX shape, before the computed
     * agent_count / listing_count / agents fields are attached. Optional fields
     * are intentionally omitted on some branches (e.g. no logo_url on Garden
     * Route, no ppra_number on Dolphin Coast) to exercise the agency-default
     * fallback and hide-when-blank paths.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function branchDefs(): array
    {
        return [
            [
                'id' => 1,
                'trading_name' => 'Home Finders Coastal — Atlantic Seaboard',
                'tagline' => 'Cape Town’s coastline, from Bloubergstrand to Gordon’s Bay.',
                'address' => '12 Victoria Road, Camps Bay, Cape Town, 8005',
                'phone' => '021 555 0140',
                'phone_label' => 'Office',
                'phone_secondary' => '082 555 0140',
                'phone_secondary_label' => 'After hours',
                'email' => 'atlantic@homefinderscoastal.com',
                'ppra_number' => 'PPRA-ATL-2025',
                'logo_url' => 'https://images.unsplash.com/photo-1554995207-c18c203602cb?auto=format&fit=crop&w=400&q=70',
            ],
            [
                'id' => 2,
                'trading_name' => 'Home Finders Coastal — Garden Route',
                'tagline' => 'Hermanus, Plettenberg Bay, Knysna & Mossel Bay.',
                'address' => '8 Main Street, Plettenberg Bay, 6600',
                'phone' => '044 555 0142',
                'phone_label' => 'Office',
                'email' => 'gardenroute@homefinderscoastal.com',
                'ppra_number' => 'PPRA-GRT-2025',
                // logo_url omitted on purpose → falls back to the agency logo.
            ],
            [
                'id' => 3,
                'trading_name' => 'Home Finders Coastal — Dolphin Coast',
                'tagline' => 'Umhlanga, Ballito & Salt Rock on the KZN coast.',
                'address' => '5 Compensation Road, Ballito, 4420',
                'phone' => '032 555 0144',
                'phone_label' => 'Office',
                'email' => 'dolphincoast@homefinderscoastal.com',
                // ppra_number omitted on purpose → hidden on the card.
                'logo_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=400&q=70',
            ],
        ];
    }

    /**
     * All demo branches in raw CoreX shape, each with its computed agent_count,
     * listing_count and nested public agents.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function branches(): array
    {
        $allAgents = self::agents();
        $allListings = self::listings();

        return array_map(static function (array $def) use ($allAgents, $allListings): array {
            $agents = array_values(array_filter(
                $allAgents,
                static fn (array $agent): bool => ($agent['branch_id'] ?? null) === $def['id'],
            ));

            $listingCount = count(array_filter(
                $allListings,
                static fn (array $listing): bool => ($listing['branch_id'] ?? null) === $def['id'],
            ));

            return array_merge($def, [
                'agent_count' => count($agents),
                'listing_count' => $listingCount,
                'agents' => $agents,
            ]);
        }, self::branchDefs());
    }

    /**
     * Find a single branch by id.
     *
     * @return array<string, mixed>
     */
    public static function findBranch(int|string $id): array
    {
        foreach (self::branches() as $branch) {
            if ((string) $branch['id'] === (string) $id) {
                return $branch;
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

    /**
     * All demo articles in raw CoreX shape.
     *
     * @param  array<string, mixed>  $query  Supports an `agent_id` filter.
     * @return array<int, array<string, mixed>>
     */
    public static function articles(array $query = []): array
    {
        $articles = [];

        for ($i = 1; $i <= self::ARTICLE_COUNT; $i++) {
            $articles[] = self::makeArticle($i);
        }

        if (isset($query['agent_id'])) {
            $agentId = (string) $query['agent_id'];

            $articles = array_values(array_filter(
                $articles,
                static fn (array $a): bool => (string) ($a['agent_id'] ?? '') === $agentId,
            ));
        }

        return $articles;
    }

    /**
     * Find a single article by id.
     *
     * @return array<string, mixed>
     */
    public static function findArticle(int|string $id): array
    {
        foreach (self::articles() as $article) {
            if ((string) $article['id'] === (string) $id) {
                return $article;
            }
        }

        return [];
    }

    /** @var list<array{string, string, list<string>}> Article title, lead, tags. */
    private const ARTICLE_TOPICS = [
        ['Pre-Approval vs Final Bond Approval', 'When buying a property, knowing the difference between pre-approval and final bond approval can save you weeks of stress.', ['BondApproval', 'HomeBuying']],
        ['5 Questions to Ask Before You Make an Offer', 'A strong offer starts long before you sign — these are the questions that protect you.', ['Buying', 'OfferToPurchase']],
        ['Staging Your Home to Sell on the Coast', 'Coastal buyers are buying a lifestyle. Here is how to present your home so they can picture themselves living it.', ['Selling', 'HomeStaging']],
        ['Understanding Levies and Special Levies', 'Sectional-title living comes with monthly levies — and the occasional special levy. Here is what to expect.', ['SectionalTitle', 'Levies']],
        ['Is Now a Good Time to Buy?', 'Interest rates, stock levels and seasonality all play a part. A practical look at timing your purchase.', ['MarketUpdate', 'Buying']],
        ['The True Cost of Buying a Home', 'Transfer duty, bond costs, attorney fees — the price on the listing is only the start.', ['HomeBuying', 'Costs']],
        ['Why a Sole Mandate Sells for More', 'A focused marketing effort beats a scattered one. The case for an exclusive mandate.', ['Selling', 'SoleMandate']],
        ['Moving With Pets to the Coast', 'A calm, well-planned move makes all the difference for four-legged family members.', ['Lifestyle', 'Moving']],
        ['First-Time Buyer? Start Here', 'A plain-language roadmap from saving a deposit to collecting your keys.', ['FirstTimeBuyer', 'HomeBuying']],
    ];

    /**
     * @return array<string, mixed>
     */
    private static function makeArticle(int $i): array
    {
        [$title, $lead, $tags] = self::ARTICLE_TOPICS[($i - 1) % count(self::ARTICLE_TOPICS)];
        $agent = self::makeAgent((($i - 1) % self::AGENT_COUNT) + 1);
        $body = self::articleBody($lead);
        $wordCount = str_word_count($body);

        return [
            'id' => 700 + $i,
            'agent_id' => $agent['id'],
            'title' => $title,
            'slug' => self::slugify($title),
            'excerpt' => $lead,
            // Every 4th article has no cover to exercise the placeholder tile.
            'cover_image_url' => $i % 4 === 0
                ? null
                : 'https://images.unsplash.com/'.self::IMAGES[$i % count(self::IMAGES)].'?auto=format&fit=crop&w=1200&q=70',
            'body' => $body,
            // A couple of articles carry an external "Read more" link.
            'link_url' => $i % 3 === 0 ? 'https://homefinderscoastal.com/blog' : null,
            'tags' => $tags,
            'read_minutes' => max(1, (int) ceil($wordCount / 200)),
            'word_count' => $wordCount,
            'date' => date('Y-m-d', strtotime('-'.($i * 11).' days')),
        ];
    }

    private static function articleBody(string $lead): string
    {
        return $lead."\n\n"
            .'It is one of the most common questions we hear, and the answer matters more than most '
            .'buyers realise. Getting it right up front means a smoother offer, a faster transfer and '
            ."far fewer surprises along the way.\n\n"
            .'Start by speaking to a bond originator early — ideally before you fall in love with a '
            .'home. They will give you a realistic budget and flag anything that needs tidying up on '
            ."your credit profile.\n\n"
            .'From there, work with a practitioner who knows the area. Local knowledge is the '
            .'difference between a fair price and a great one, and it is exactly what we bring to '
            ."every mandate along this coastline.\n\n"
            .'Have a question of your own? Get in touch — we are always happy to help.';
    }

    private static function slugify(string $title): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));

        return trim($slug, '-');
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
            // A listing belongs to the same branch as its attributed agent.
            'branch_id' => ((($i - 1) % self::AGENT_COUNT) % self::BRANCH_COUNT) + 1,
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
            // Every 4th listing is co-listed by a second agent, exercising the
            // multi-agent card + detail rendering. CoreX embeds these in an
            // `agents` array (primary repeated), which the mapper dedupes.
            ...($i % 4 === 0 ? ['agents' => [
                self::makeAgent((($i - 1) % self::AGENT_COUNT) + 1),
                self::makeAgent(($i % self::AGENT_COUNT) + 1),
            ]] : []),
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
        $handle = strtolower($first.preg_replace('/[^a-z]/i', '', $last));

        return [
            'id' => 20 + $i,
            'branch_id' => (($i - 1) % self::BRANCH_COUNT) + 1,
            'name' => "{$first} {$last}",
            'designation' => self::AGENT_DESIGNATIONS[($i - 1) % count(self::AGENT_DESIGNATIONS)],
            'email' => "{$slug}@homefinderscoastal.com",
            'cell' => sprintf('+27 8%d %03d %04d', $i % 5 + 1, ($i * 137) % 1000, ($i * 911) % 10000),
            'photo_url' => "https://i.pravatar.cc/600?img={$i}",
            // A couple of agents have no bio (every 4th) to exercise the
            // hide-when-null "Get to Know Me" path.
            'about' => $i % 4 === 0 ? null : self::about($first, $i),
            'socials' => self::agentSocials($i, $handle),
        ];
    }

    private static function about(string $first, int $i): string
    {
        $suburb = self::SUBURBS[($i - 1) % count(self::SUBURBS)][0];

        return "With over a decade selling along the coast, {$first} pairs deep local knowledge of "
            ."{$suburb} with a calm, honest approach to every mandate. Whether you're buying your "
            ."first home or selling a clifftop estate, you'll have a practitioner who knows the street, "
            ."the neighbours and exactly what your property is worth.\n\n"
            .'Get in touch for an obligation-free valuation or a quiet chat about the market.';
    }

    /**
     * A varied socials object — agents fill in different platforms, and an
     * Instagram handle (not a full URL) exercises the normalisation path. Every
     * 5th agent has no socials at all to exercise the hide-when-empty path.
     *
     * @return array<string, string>
     */
    private static function agentSocials(int $i, string $handle): array
    {
        if ($i % 5 === 0) {
            return [];
        }

        $socials = [
            'facebook' => "https://facebook.com/{$handle}.hfc",
            'instagram' => $handle, // bare handle on purpose
        ];

        if ($i % 2 === 0) {
            $socials['linkedin'] = "https://linkedin.com/in/{$handle}";
        }

        if ($i % 3 === 0) {
            $socials['youtube'] = "https://youtube.com/@{$handle}";
        }

        return $socials;
    }
}
