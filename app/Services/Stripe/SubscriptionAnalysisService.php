<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionAnalysisService
{
    private $newCustomer;
    private $newSubscription;
    public function __construct(
        private readonly StripeCustomerService $customerService,
        private readonly StripeSubscriptionService $subscriptionService,
        private readonly StripeTestClockService $clockService,
        private readonly string $stripeTestClockId,
        private readonly string $newSubscriptionCouponId,
        private readonly string $newSubscriptionPriceId,
        private readonly string $upgradeSubscriptionPriceId,
    )
    {}

    private function addDataToStripeBeforeAnalysis()
    {
        //Create new customer in Stripe
        $this->newCustomer = $this->customerService->createCustomer(
            'Brendan Smith',
            'brendan.smith@example.com',
            'pm_card_visa',
            $this->stripeTestClockId,
        );
        //Create new subscription in Stripe
        $this->newSubscription = $this->subscriptionService->createSubscription(
            $this->newCustomer->id,
            $this->newSubscriptionPriceId,
            $this->newSubscriptionCouponId,
            30,
            'gbp',
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function getAnalysisData(): array
    {
        $this->addDataToStripeBeforeAnalysis();
        $data = [];
        //Stripe time clock is configured to start at 1704110400 (January 1st, 2024 @ 2:00PM GMT)
        $times = [
            //TODO: generate timestamps from CarbonImmutable objects for more transparency
            1706796000, //February 1st, 2024 @ 2:00PM GMT
            1709301600, //March 1st, 2024 @ 2:00PM GMT
            1711980000, //April 1st, 2024 @ 2:00PM GMT
            1714572000, //May 1st, 2024 @ 2:00PM GMT
            1717250400, //June 1st, 2024 @ 2:00PM GMT
            1719842400, //July 1st, 2024 @ 2:00PM GMT
            1722520800, //August 1st, 2024 @ 2:00PM GMT
            1725199200, //September 1st, 2024 @ 2:00PM GMT
            1727791200, //October 1st, 2024 @ 2:00PM GMT
            1730469600, //November 1st, 2024 @ 2:00PM GMT
            1733061600, //December 1st, 2024 @ 2:00PM GMT
            1735740000, //January 1st, 2025 @ 2:00PM GMT
        ];
        foreach ($times as $time) {
            $clock = $this->clockService->advanceClockAndPollUntilReady($this->stripeTestClockId, $time);
            Log::info("Advanced Stripe Time Clock: {$clock->name} ({$clock->id})", [
                'frozen_time' => $clock->frozen_time,
                'status' => $clock->status,
            ]);
        }
        return $data;
    }
}