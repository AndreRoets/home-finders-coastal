<?php

namespace App\Providers;

use App\Services\Corex\CorexClient;
use App\Services\Corex\DemoCorexClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CorexClient::class, function (): CorexClient {
            if (config('services.corex.demo')) {
                return new DemoCorexClient;
            }

            return new CorexClient(
                baseUrl: (string) config('services.corex.base_url'),
                apiKey: config('services.corex.api_key'),
                timeout: (int) config('services.corex.timeout'),
                cacheTtl: (int) config('services.corex.cache_ttl'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
