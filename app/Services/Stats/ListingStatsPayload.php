<?php

namespace App\Services\Stats;

use App\Models\ListingStat;
use Illuminate\Support\Collection;

/**
 * Builds the JSON body the website POSTs to CoreX's listing-stats endpoint.
 *
 * Per listing it reports three views of the same numbers, so CoreX can
 * implement whichever it needs without the website having to know:
 *
 * - `days`   — the outstanding per-day deltas, i.e. a time series to append.
 * - `delta`  — the sum of `days`, i.e. the amount to add to running totals.
 * - `totals` — the website's lifetime totals, i.e. a value to reconcile against
 *              (useful to detect and repair drift after a missed batch).
 *
 * `delta` is by construction the sum of every `days[].metrics` entry.
 *
 * @phpstan-type Metrics array<string, int>
 */
class ListingStatsPayload
{
    /**
     * Assemble the request body from every stat row belonging to the listings
     * in this batch. Rows with nothing outstanding still contribute to
     * `totals` — they are what makes reconciliation possible.
     *
     * @param  Collection<int, ListingStat>  $rows
     * @return array<string, mixed>
     */
    public static function build(Collection $rows, string $batchId, string $site): array
    {
        $listings = $rows
            ->groupBy('listing_id')
            ->map(static fn (Collection $listingRows, string $listingId): array => self::listing($listingId, $listingRows))
            // A listing whose rows are all fully pushed has nothing to report.
            ->filter(static fn (array $listing): bool => $listing['delta'] !== [])
            ->values()
            ->all();

        return [
            'source' => 'website',
            'site' => $site,
            'batch_id' => $batchId,
            'generated_at' => now()->toIso8601String(),
            'listings' => $listings,
        ];
    }

    /**
     * One listing's entry: its identifiers plus the three metric views.
     *
     * @param  Collection<int, ListingStat>  $rows
     * @return array{listing_id: string, reference: string|null, days: array<int, array{date: string, metrics: Metrics}>, delta: Metrics, totals: Metrics}
     */
    protected static function listing(string $listingId, Collection $rows): array
    {
        $outstanding = $rows->filter(static fn (ListingStat $row): bool => $row->delta() > 0);

        $days = $outstanding
            ->groupBy(static fn (ListingStat $row): string => $row->date->toDateString())
            ->map(static fn (Collection $dayRows): array => $dayRows
                ->mapWithKeys(static fn (ListingStat $row): array => [$row->event->value => $row->delta()])
                ->all())
            ->sortKeys()
            ->map(static fn (array $metrics, string $date): array => ['date' => $date, 'metrics' => $metrics])
            ->values()
            ->all();

        return [
            'listing_id' => $listingId,
            'reference' => $rows->first(static fn (ListingStat $row): bool => $row->reference !== null)?->reference,
            'days' => $days,
            'delta' => self::sum($outstanding, static fn (ListingStat $row): int => $row->delta()),
            'totals' => self::sum($rows, static fn (ListingStat $row): int => $row->count),
        ];
    }

    /**
     * Total one value per event across the given rows, keyed by metric name.
     *
     * @param  Collection<int, ListingStat>  $rows
     * @param  callable(ListingStat): int  $value
     * @return Metrics
     */
    protected static function sum(Collection $rows, callable $value): array
    {
        return $rows
            ->groupBy(static fn (ListingStat $row): string => $row->event->value)
            ->map(static fn (Collection $eventRows): int => (int) $eventRows->sum($value))
            ->sortKeys()
            ->all();
    }
}
