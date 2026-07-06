<?php

namespace App\Console\Commands;

use App\Services\Corex\CorexClient;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Operational safety net: clear every cached CoreX response (the `corex:*`
 * keys written by {@see CorexClient}) so the next page view
 * re-pulls fresh data from CoreX.
 *
 * This is NOT wired to anything — the webhook busts caches surgically on its
 * own. Run it by hand when a cache is suspected stale and you can't wait for
 * the TTL (COREX_CACHE_TTL), e.g. after a missed/failed webhook delivery:
 *
 *   php artisan corex:cache-flush
 *
 * The default cache store is the database driver, so we delete the matching
 * rows directly (targeted, leaving non-CoreX cache entries untouched). On any
 * other store we fall back to a full cache flush and say so.
 */
class CorexCacheFlush extends Command
{
    /**
     * @var string
     */
    protected $signature = 'corex:cache-flush';

    /**
     * @var string
     */
    protected $description = 'Clear all cached CoreX responses (corex:* cache keys)';

    public function handle(): int
    {
        $store = Cache::store()->getStore();

        if ($store instanceof DatabaseStore) {
            $deleted = $this->flushDatabaseStore($store);

            $this->info("Cleared {$deleted} corex:* cache entr".($deleted === 1 ? 'y' : 'ies').'.');

            return self::SUCCESS;
        }

        // Non-database stores (array/file/redis without tag support here) don't
        // offer a portable prefix scan, so flush everything and be explicit.
        Cache::flush();

        $this->warn('Cache store is not the database driver; flushed the entire cache.');

        return self::SUCCESS;
    }

    /**
     * Delete every `corex:*` row from the database cache table, honouring the
     * store's key prefix and configured connection/table.
     */
    protected function flushDatabaseStore(DatabaseStore $store): int
    {
        $prefix = $store->getPrefix();
        $table = config('cache.stores.database.table', 'cache');
        $connection = config('cache.stores.database.connection');

        return DB::connection($connection)
            ->table($table)
            ->where('key', 'like', $prefix.'corex:%')
            ->delete();
    }
}
