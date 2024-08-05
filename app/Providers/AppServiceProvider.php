<?php

declare(strict_types=1);

namespace App\Providers;

use App\Console\Commands\GenerateSubscriptionAnalysis;
use App\Services\Stripe\StripeCouponService;
use App\Services\Stripe\StripeCustomerService;
use App\Services\Stripe\StripeInvoiceService;
use App\Services\Stripe\StripePriceService;
use App\Services\Stripe\StripeProductService;
use App\Services\Stripe\StripeSubscriptionService;
use App\Services\Stripe\StripeTestClockService;
use App\Services\SubscriptionAnalysis\SubscriptionAnalysisService;
use Carbon\CarbonImmutable;
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
        $startTime = CarbonImmutable::createFromTimestamp(config('services.stripe.subscription_analysis.start_time'));
        $endTime = $startTime->addYear()->subDay();

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

        $this->app->bind(SubscriptionAnalysisService::class, function(Application $app) use ($startTime, $endTime) {
            return new SubscriptionAnalysisService(
                app(StripePriceService::class),
                app(StripeCouponService::class),
                app(StripeCustomerService::class),
                app(StripeSubscriptionService::class),
                app(StripeTestClockService::class),
                app(StripeProductService::class),
                app(StripeInvoiceService::class),
                $startTime,
                $endTime,
            );
        });

        $this->app->bind(GenerateSubscriptionAnalysis::class, function(Application $app) use ($startTime, $endTime) {
            return new GenerateSubscriptionAnalysis(
                $startTime,
                $endTime,
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
