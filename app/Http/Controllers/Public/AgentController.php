<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Corex\AgentMapper;
use App\Services\Corex\CorexClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * List the agency's agents.
     *
     * CoreX's standalone /agents endpoint is often empty (agents aren't
     * always syndicated to the website key), so when it returns nothing we
     * fall back to the agents embedded on the listings, deduplicated by id.
     */
    public function index(): Response
    {
        $agents = $this->corex->agents();

        if ($agents === []) {
            $agents = $this->agentsFromListings();
        }

        return Inertia::render('agents', [
            'agents' => AgentMapper::collection($agents),
        ]);
    }

    /**
     * Extract unique agents embedded on the syndicated listings.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function agentsFromListings(): array
    {
        return Collection::make($this->corex->listings())
            ->map(fn (array $listing): mixed => Arr::get($listing, 'agent'))
            ->filter(fn (mixed $agent): bool => is_array($agent) && Arr::has($agent, 'id'))
            ->unique(fn (array $agent): mixed => $agent['id'])
            ->values()
            ->all();
    }
}
