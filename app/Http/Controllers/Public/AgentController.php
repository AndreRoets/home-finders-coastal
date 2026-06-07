<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchContext;
use App\Services\Corex\AgentMapper;
use App\Services\Corex\ArticleMapper;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use App\Services\Corex\TestimonialMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AgentController extends Controller
{
    public function __construct(
        protected CorexClient $corex,
        protected BranchContext $branches,
    ) {}

    /**
     * List the agency's agents.
     *
     * CoreX's standalone /agents endpoint is often empty (agents aren't
     * always syndicated to the website key), so when it returns nothing we
     * fall back to the agents embedded on the listings, deduplicated by id.
     */
    public function index(): Response
    {
        $branchQuery = $this->branches->query();

        $agents = $this->corex->agents($branchQuery);

        if ($agents === []) {
            $agents = $this->agentsFromListings($branchQuery);
        }

        return Inertia::render('agents', [
            'agents' => AgentMapper::collection($agents),
            'branches' => $this->branches->summaries(),
            'activeBranch' => $this->branches->activeId(),
        ]);
    }

    /**
     * Agent detail page: the agent's profile, their listings and the
     * testimonials about them.
     *
     * CoreX's standalone /agents/{id} can 404 (the agent may not be syndicated
     * to the website key) even when the agent appears on listings, so when it
     * returns nothing we fall back to the agent embedded on this agent's
     * listings. Only when neither yields an agent do we render a graceful
     * "not found" page with a 404 status.
     */
    public function show(Request $request, int|string $id): HttpResponse
    {
        $listings = $this->corex->listings(['agent_id' => $id]);

        $agent = $this->corex->agent($id);

        if ($agent === []) {
            $agent = $this->agentFromListings($listings, $id);
        }

        if ($agent === []) {
            return Inertia::render('agent', ['agent' => null])
                ->toResponse($request)
                ->setStatusCode(HttpResponse::HTTP_NOT_FOUND);
        }

        return Inertia::render('agent', [
            'agent' => AgentMapper::map($agent),
            'listings' => ListingMapper::collection($listings),
            'testimonials' => TestimonialMapper::collection(
                $this->corex->testimonials(['agent_id' => $id]),
            ),
            'articles' => ArticleMapper::collection(
                $this->corex->articles(['agent_id' => $id]),
            ),
        ])->toResponse($request);
    }

    /**
     * Find the agent matching $id embedded on one of the given listings.
     *
     * @param  array<int, array<string, mixed>>  $listings
     * @return array<string, mixed>
     */
    protected function agentFromListings(array $listings, int|string $id): array
    {
        foreach ($listings as $listing) {
            $agent = Arr::get($listing, 'agent');

            if (is_array($agent) && (string) Arr::get($agent, 'id') === (string) $id) {
                return $agent;
            }
        }

        return [];
    }

    /**
     * Extract unique agents embedded on the syndicated listings, optionally
     * scoped to a branch via the query (e.g. ['branch_id' => 12]).
     *
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    protected function agentsFromListings(array $query = []): array
    {
        return Collection::make($this->corex->listings($query))
            ->map(fn (array $listing): mixed => Arr::get($listing, 'agent'))
            ->filter(fn (mixed $agent): bool => is_array($agent) && Arr::has($agent, 'id'))
            ->unique(fn (array $agent): mixed => $agent['id'])
            ->values()
            ->all();
    }
}
