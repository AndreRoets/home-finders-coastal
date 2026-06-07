<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Branches\BranchMapper;
use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use App\Services\Corex\ListingMapper;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BranchController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * "Our Branches" overview — one card per branch. 404s when the agency has the
     * branches feature switched off (data.show.branches), so the page never
     * exists for agencies that haven't enabled it.
     */
    public function index(): Response
    {
        $agency = AgencyMapper::map($this->corex->agency());

        $this->abortUnlessEnabled($agency);

        return Inertia::render('branches', [
            'branches' => BranchMapper::collection($this->corex->branches(), $agency ?? []),
        ]);
    }

    /**
     * A single branch: its contact block, the agents in that office and the
     * properties on its books. Same feature gate as the overview; 404s for an
     * unknown branch id.
     */
    public function show(int|string $id): Response
    {
        $agency = AgencyMapper::map($this->corex->agency());

        $this->abortUnlessEnabled($agency);

        $branch = $this->corex->branch($id);

        if ($branch === []) {
            throw new NotFoundHttpException('Branch not found.');
        }

        return Inertia::render('branch', [
            'branch' => BranchMapper::map($branch, $agency ?? []),
            'listings' => ListingMapper::collection($this->corex->listings(['branch_id' => $id])),
        ]);
    }

    /**
     * The branches feature is opt-in per agency. When off (or when the agency
     * profile can't be loaded at all), the offices pages must not exist.
     *
     * @param  array<string, mixed>|null  $agency  The mapped agency, or null on a fetch error.
     */
    protected function abortUnlessEnabled(?array $agency): void
    {
        if (($agency['show']['branches'] ?? false) !== true) {
            throw new NotFoundHttpException('The branches feature is not enabled.');
        }
    }
}
