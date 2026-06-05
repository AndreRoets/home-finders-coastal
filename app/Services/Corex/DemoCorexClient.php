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
            'tagline' => 'The Mandate Company',
            'email' => 'hello@homefinderscoastal.com',
            'phone' => '+27 21 000 0000',
        ];
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    public function agents(array $query = []): array
    {
        return DemoData::agents();
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
        return DemoData::listings();
    }

    /**
     * @return array<string, mixed>
     */
    public function listing(int|string $idOrRef): array
    {
        return DemoData::findListing($idOrRef);
    }
}
