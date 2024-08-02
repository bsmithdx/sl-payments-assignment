<?php

namespace App\Providers;

use App\Services\Stripe\StripeCustomerService;
use App\Services\Stripe\StripeSubscriptionService;
use App\Services\Stripe\StripeTestClockService;
use App\Services\Stripe\SubscriptionAnalysisService;
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
                config('services.stripe.test_clock.max_attempts'),
            );
        });

        $this->app->bind(SubscriptionAnalysisService::class, function(Application $app) {
            return new SubscriptionAnalysisService(
                app(StripeCustomerService::class),
                app(StripeSubscriptionService::class),
                app(StripeTestClockService::class),
                config('services.stripe.test_clock.id'),
                config('services.stripe.subscription_analysis.new_subscription_coupon_id'),
                config('services.stripe.subscription_analysis.new_subscription_price_id'),
                config('services.stripe.subscription_analysis.upgrade_subscription_price_id'),
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
