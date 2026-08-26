<?php

namespace App\Services\Corex;

/**
 * A drop-in {@see CorexClient} that serves the local {@see DemoData} set
 * instead of hitting the live CoreX API. Bound in place of CorexClient when
 * COREX_DEMO is enabled so the public site is fully populated for local work.
 */
class DemoCorexClient extends CorexClient
{
    public function __construct()
    {
        parent::__construct(baseUrl: '', apiKey: null, timeout: 1, cacheTtl: 0);
    }

    public function ping(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function agency(): array
    {
        return [
            'name' => 'Home Finders Coastal',
            'trading_name' => 'Home Finders Coastal — The Mandate Company',
            'logo_url' => '/images/hfc-logo.png',
            'branding' => [
                'sidebar_color' => '#0b1f33',
                'icon_color' => '#1f7a8c',
                'default_color' => '#0b1f33',
                'button_color' => '#1f7a8c',
            ],
            'contact' => [
                'email' => 'info@homefinderscoastal.com',
                'phone' => '039 315 0140',
                'address' => '12 Marina Drive, Uvongo, 4275',
            ],
            'social' => [
                'facebook' => 'homefinderscoastal', // bare handle on purpose
                'instagram' => 'https://instagram.com/homefinderscoastal',
                'linkedin' => 'homefinderscoastal',
                'youtube' => 'homefinderscoastal',
            ],
            'open_hours' => [
                ['days' => 'Monday – Friday', 'hours' => '08:00 – 17:00'],
                ['days' => 'Saturday', 'hours' => '09:00 – 13:00'],
                ['days' => 'Sunday', 'hours' => 'Closed'],
            ],
            'show' => ['agents' => true, 'listings' => true, 'branches' => true],
            'agent_order_mode' => 'alphabetical',
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function agents(array $query = []): array
    {
        $agents = DemoData::agents();

        if (isset($query['branch_id'])) {
            $branchId = (int) $query['branch_id'];

            $agents = array_values(array_filter(
                $agents,
                static fn (array $agent): bool => ($agent['branch_id'] ?? null) === $branchId,
            ));
        }

        return $agents;
    }

    /**
     * @return array<string, mixed>
     */
    public function agent(int|string $id): array
    {
        return DemoData::findAgent($id);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function listings(array $query = []): array
    {
        $listings = DemoData::listings();

        if (isset($query['agent_id'])) {
            $agentId = (string) $query['agent_id'];

            $listings = array_values(array_filter(
                $listings,
                static fn (array $listing): bool => (string) ($listing['agent']['id'] ?? '') === $agentId,
            ));
        }

        if (isset($query['branch_id'])) {
            $branchId = (int) $query['branch_id'];

            $listings = array_values(array_filter(
                $listings,
                static fn (array $listing): bool => ($listing['branch_id'] ?? null) === $branchId,
            ));
        }

        return $listings;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function branches(array $query = []): array
    {
        return DemoData::branches();
    }

    /**
     * @return array<string, mixed>
     */
    public function branch(int|string $id): array
    {
        return DemoData::findBranch($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function listing(int|string $idOrRef): array
    {
        return DemoData::findListing($idOrRef);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function testimonials(array $query = []): array
    {
        return DemoData::testimonials($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function testimonial(int|string $id): array
    {
        return DemoData::findTestimonial($id);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function articles(array $query = []): array
    {
        return DemoData::articles($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function article(int|string $id): array
    {
        return DemoData::findArticle($id);
    }

    /**
     * Demo mode has no live CoreX to receive leads, so accept the enquiry
     * without hitting the network and report success.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createLead(array $payload): bool
    {
        return true;
    }

    /**
     * Demo mode has no live CoreX to receive stats. Report success so the
     * local counters still advance and the push command can be exercised
     * end to end without a network call.
     *
     * @param  array<string, mixed>  $payload
     */
    public function pushListingStats(array $payload): bool
    {
        return true;
    }
}
