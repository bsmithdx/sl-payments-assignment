<?php

namespace App\Providers;

use App\Services\SubscriptionAnalysisService;
use App\Services\Stripe\StripeTestClockService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function(Application $app) {
            return new StripeClient(config('services.stripe.secret'));
        });

        $this->app->bind(StripeTestClockService::class, function(Application $app) {
            return new StripeTestClockService(
                app(StripeClient::class),
                config('services.stripe.test_clock.initial_timeout'),
                config('services.stripe.test_clock.backoff_increment'),
            );
        });

        $this->app->bind(SubscriptionAnalysisService::class, function(Application $app) {
            return new SubscriptionAnalysisService(
                app(StripeTestClockService::class),
                config('services.stripe.test_clock.id'),
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
