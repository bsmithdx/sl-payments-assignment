<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\DTO\SubscriptionAnalysis\SubscriptionAnalysisDTO;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionAnalysisService
{
    public function __construct(
        private readonly StripePriceService        $priceService,
        private readonly StripeCouponService       $couponService,
        private readonly StripeCustomerService     $customerService,
        private readonly StripeSubscriptionService $subscriptionService,
        private readonly StripeTestClockService    $clockService,
        private readonly StripeInvoiceService      $invoiceService,
        private readonly string                    $stripeTestClockId,
        private readonly CarbonImmutable           $startTime,
    )
    {}

    private function addDataToStripeBeforeAnalysis()
    {
        $prices = $this->priceService->getPricesByLookupKeys([
            'monthly_crossclip_basic',
            'monthly_crossclip_premium',
        ]);
        $pricesByLookupKey = [];
        foreach ($prices->data as $price) {
            $pricesByLookupKey[$price->lookup_key] = $price;
        }
        //Create new customer in Stripe
        $newCustomer = $this->customerService->createCustomer(
            'Brendan Smith',
            'brendan.smith@example.com',
            'pm_card_visa',
            $this->stripeTestClockId,
        );
        //Create new subscription in Stripe
        $newSubscription = $this->subscriptionService->createSubscription(
            $newCustomer->id,
            $pricesByLookupKey['monthly_crossclip_basic']->id,
            $this->couponService->getCouponByName('10 Dollar Off'),
            30,
            'gbp',
        );
        //Set schedule to upgrade subscription based on timestamp and priceid
        $this->subscriptionService->upgradeExistingSubscriptionWithSchedule(
            $pricesByLookupKey['monthly_crossclip_premium']->id,
            $newSubscription->id,
            $this->startTime->addMonths(5)->setDay(15),
        );
    }

    private function getAnalysisData()
    {
        //get all customers associated with the test clock
        $customers = $this->customerService->getAllCustomers($this->stripeTestClockId);
        $customerEmailsById = [];
        foreach ($customers->data as $customer) {
            $customerEmailsById[$customer->id] = $customer->email;
        }
        //apparently Stripe won't let you retrieve all invoices without providing a customerId so we'll have to loop through customers to get all invoices.
        foreach ($customerEmailsById as $customerId => $customerEmail) {
            $invoices = $this->invoiceService->getAllInvoicesForCustomer($customerId);
            //TODO: combine invoices and process into all data necessary for table
        }
        return [];

    }

    /**
     * @throws ApiErrorException
     */
    public function runAnalysis(): SubscriptionAnalysisDTO
    {
        $this->addDataToStripeBeforeAnalysis();
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
        return $this->getAnalysisData();
    }
}