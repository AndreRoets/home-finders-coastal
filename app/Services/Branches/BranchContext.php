<?php

namespace App\Services\Branches;

use App\Services\Corex\AgencyMapper;
use App\Services\Corex\CorexClient;
use Illuminate\Support\Arr;

/**
 * Resolves the branch filter context for the current request on the listing and
 * agent index pages: the list of branches to offer ("All" + each branch) and
 * which one (if any) is selected via the `?branch_id={id}` query parameter.
 *
 * Everything is gated behind the agency's show.branches feature toggle and fails
 * soft to "no branches" — so the filter simply doesn't appear when the feature
 * is off or the branches feed is unavailable. Results are memoised per request.
 */
class BranchContext
{
    /** @var array<string, mixed>|null */
    private ?array $agency = null;

    private bool $agencyLoaded = false;

    /** @var array<int, array<string, mixed>>|null */
    private ?array $branches = null;

    public function __construct(protected CorexClient $corex) {}

    /**
     * Whether the branches feature is enabled for this agency.
     */
    public function enabled(): bool
    {
        return ($this->agency()['show']['branches'] ?? false) === true;
    }

    /**
     * Lightweight branch options for the filter control ("All" is added on the
     * frontend). Empty when the feature is off.
     *
     * @return array<int, array{id: int|string, name: string}>
     */
    public function summaries(): array
    {
        if (! $this->enabled()) {
            return [];
        }

        return array_values(array_map(
            static fn (array $branch): array => [
                'id' => Arr::get($branch, 'id'),
                'name' => (string) (Arr::get($branch, 'trading_name') ?: 'Branch'),
            ],
            $this->rawBranches(),
        ));
    }

    /**
     * The selected branch id for this request, or null for "All". Only returns
     * an id that matches a real branch, so a stale/forged ?branch_id is ignored.
     */
    public function activeId(): int|string|null
    {
        if (! $this->enabled()) {
            return null;
        }

        $requested = request()->query('branch_id');

        if ($requested === null || $requested === '') {
            return null;
        }

        foreach ($this->rawBranches() as $branch) {
            if ((string) Arr::get($branch, 'id') === (string) $requested) {
                return Arr::get($branch, 'id');
            }
        }

        return null;
    }

    /**
     * The CoreX query parameters to scope a listings/agents request to the
     * active branch — `['branch_id' => id]`, or `[]` for "All".
     *
     * @return array<string, mixed>
     */
    public function query(): array
    {
        $id = $this->activeId();

        return $id !== null ? ['branch_id' => $id] : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function agency(): ?array
    {
        if (! $this->agencyLoaded) {
            $this->agency = AgencyMapper::map($this->corex->agency());
            $this->agencyLoaded = true;
        }

        return $this->agency;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function rawBranches(): array
    {
        return $this->branches ??= $this->corex->branches();
    }
}
