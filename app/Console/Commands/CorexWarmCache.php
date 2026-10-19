<?php

namespace App\Console\Commands;

use App\Services\Corex\CorexClient;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Keep the CoreX caches hot so no visitor ever pays for a cold fetch (the full
 * listings collection is ~14 API calls and several MB), and sweep expired rows
 * out of the database cache table, which Laravel never prunes by itself.
 *
 * Meant to run every few minutes — well inside COREX_CACHE_TTL — as the cache
 * owner (www-data):
 *
 *   php artisan corex:warm-cache
 *
 * Collections are refreshed in place, so readers keep the current copy until
 * the new one is stored, and a failed CoreX response leaves it untouched.
 */
class CorexWarmCache extends Command
{
    /**
     * @var string
     */
    protected $signature = 'corex:warm-cache';

    /**
     * @var string
     */
    protected $description = 'Refresh the cached CoreX collections and prune expired cache rows';

    public function handle(CorexClient $corex): int
    {
        $lock = Cache::lock('lock:corex:warm-cache', 600);

        if (! $lock->get()) {
            $this->warn('Another warm run is in progress; skipping.');

            return self::SUCCESS;
        }

        try {
            $refreshed = $corex->warm();
            $this->info("Refreshed {$refreshed} CoreX cache entr".($refreshed === 1 ? 'y' : 'ies').'.');

            $pruned = $this->pruneExpired();
            $this->info("Pruned {$pruned} expired cache row".($pruned === 1 ? '' : 's').'.');
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    /**
     * Delete expired rows from the database cache table. A no-op on other stores,
     * which expire entries themselves.
     */
    protected function pruneExpired(): int
    {
        if (! Cache::store()->getStore() instanceof DatabaseStore) {
            return 0;
        }

        return DB::connection(config('cache.stores.database.connection'))
            ->table(config('cache.stores.database.table', 'cache'))
            ->where('expiration', '<', now()->getTimestamp())
            ->delete();
    }
}
