<?php

namespace App\Console\Commands;

use App\Models\ListingStat;
use App\Services\Corex\CorexClient;
use App\Services\Stats\ListingStatsPayload;
use App\Services\Stats\ListingStatsRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Push the website's listing engagement counters back to CoreX.
 *
 * Views, impressions, contact clicks and enquiries are counted locally by
 * {@see ListingStatsRecorder} and shipped from here in
 * batches (scheduled hourly in routes/console.php), rather than one HTTP call
 * per page view.
 *
 * Each listing's outstanding delta is `count - pushed_count`. `pushed_count`
 * only advances on a confirmed 2xx, which makes the whole thing self-healing:
 * a failed run, a CoreX outage or a skipped schedule simply means a larger
 * delta next time, and no hit is ever counted twice. The captured count is
 * written back rather than the live column, so hits arriving mid-push are left
 * outstanding for the following run instead of being marked as sent.
 *
 *   php artisan corex:push-stats
 *   php artisan corex:push-stats --dry-run   # print the payload, send nothing
 *   php artisan corex:push-stats --prune     # also drop fully-pushed old rows
 */
class CorexPushStats extends Command
{
    /**
     * @var string
     */
    protected $signature = 'corex:push-stats {--dry-run : Print the payloads instead of sending them} {--prune : Delete fully-pushed rows older than the retention window}';

    /**
     * @var string
     */
    protected $description = 'Send website listing statistics (views, impressions, enquiries) back to CoreX';

    public function handle(CorexClient $corex): int
    {
        if (! config('services.corex.stats.enabled')) {
            $this->warn('CoreX stats reporting is disabled (COREX_STATS_ENABLED=false).');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $site = (string) config('services.corex.stats.site');
        $chunkSize = max(1, (int) config('services.corex.stats.chunk'));

        $listingIds = ListingStat::query()->pending()->distinct()->pluck('listing_id');

        if ($listingIds->isEmpty()) {
            $this->info('No outstanding listing statistics to push.');

            return $this->option('prune') ? $this->prune() : self::SUCCESS;
        }

        $pushed = 0;
        $failed = 0;

        foreach ($listingIds->chunk($chunkSize) as $batch) {
            // Every row for these listings, not just the outstanding ones — the
            // fully-pushed rows are what make `totals` a lifetime figure CoreX
            // can reconcile against.
            $rows = ListingStat::query()->whereIn('listing_id', $batch->all())->get();
            $payload = ListingStatsPayload::build($rows, (string) Str::uuid(), $site);

            if ($payload['listings'] === []) {
                continue;
            }

            if ($dryRun) {
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $pushed += count($payload['listings']);

                continue;
            }

            if (! $corex->pushListingStats($payload)) {
                $failed += count($payload['listings']);

                continue;
            }

            $this->markPushed($rows);
            $pushed += count($payload['listings']);
        }

        $verb = $dryRun ? 'Would push' : 'Pushed';
        $this->info("{$verb} statistics for {$pushed} listing".($pushed === 1 ? '' : 's').'.');

        if ($failed > 0) {
            $this->error("Failed to push {$failed} listing".($failed === 1 ? '' : 's').'; they stay outstanding for the next run.');
        }

        if ($this->option('prune') && $this->prune() !== self::SUCCESS) {
            return self::FAILURE;
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Mark the delta CoreX just accepted as sent.
     *
     * Rows are grouped by the count captured before the request so the whole
     * batch collapses into a handful of UPDATEs (counts are small integers, so
     * thousands of rows share a few dozen distinct values) instead of one query
     * per row.
     *
     * @param  Collection<int, ListingStat>  $rows
     */
    protected function markPushed(Collection $rows): void
    {
        $now = now();

        $rows->filter(static fn (ListingStat $row): bool => $row->delta() > 0)
            ->groupBy(static fn (ListingStat $row): int => $row->count)
            ->each(static function (Collection $group, int $count) use ($now): void {
                ListingStat::query()
                    ->whereIn('id', $group->pluck('id')->all())
                    ->update(['pushed_count' => $count, 'pushed_at' => $now]);
            });
    }

    /**
     * Drop rows outside the retention window that CoreX has already been told
     * about in full. Anything still outstanding is kept regardless of age so a
     * long outage can never lose counts.
     */
    protected function prune(): int
    {
        $days = max(1, (int) config('services.corex.stats.retain_days'));

        $deleted = ListingStat::query()
            ->whereColumn('count', '<=', 'pushed_count')
            ->whereDate('date', '<', now()->subDays($days)->toDateString())
            ->delete();

        $this->info("Pruned {$deleted} stat row".($deleted === 1 ? '' : 's')." older than {$days} days.");

        return self::SUCCESS;
    }
}
