<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionAnalysisService
{
    private string $stripeTestClockId;
    public function __construct(
        private readonly StripePriceService        $priceService,
        private readonly StripeCouponService       $couponService,
        private readonly StripeCustomerService     $customerService,
        private readonly StripeSubscriptionService $subscriptionService,
        private readonly StripeTestClockService    $clockService,
        private readonly StripeInvoiceService      $invoiceService,
        private readonly CarbonImmutable           $startTime,
    )
    {
        $testClock = $this->clockService->getAllClocks()->first();
        if (is_null($testClock)) {
            throw new \Exception('No Stripe Test Clock found');
        }
        $this->stripeTestClockId = $testClock->id;
    }

    /**
     * @throws ApiErrorException
     */
    public function addDataToStripeBeforeAnalysis(): void
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
        //The 30 day trial can lead to different results in the final table depending on the length of the month we start in
        $trialEnds = $this->startTime->addDays(30);
        //if starting Feb 1, 2024 then this should result in upgrading on the 15th of June (half-way through the "5th month")
        $upgradeStartTime = $this->startTime->addMonths(4)->setDay(15);
        $params = [
            'customer' => $newCustomer->id,
            'start_date' => 'now',
            'end_behavior' => 'release',
            'phases' => [
                [
                    'items' => [
                        [
                            'price' => $pricesByLookupKey['monthly_crossclip_basic']->id,
                            'quantity' => 1,
                        ],
                    ],
                    'currency' => 'gbp',
                    'end_date' => $trialEnds->getTimestamp(),
                    'trial_end' => $trialEnds->getTimestamp(),
                ],
                [
                    'items' => [
                        [
                            'price' => $pricesByLookupKey['monthly_crossclip_basic']->id,
                            'quantity' => 1,
                        ],
                    ],
                    'currency' => 'gbp',
                    'coupon' => $this->couponService->getCouponByName('5 Dollar Off for 3 Months')->id,
                    'end_date' => $upgradeStartTime->getTimestamp(),
                ],
                [
                    'proration_behavior' => 'always_invoice',
                    'items' => [
                        [
                            'price' => $pricesByLookupKey['monthly_crossclip_premium']->id,
                            'quantity' => 1,
                        ],
                    ],
                    'currency' => 'gbp',
                    'collection_method' => 'charge_automatically',
                ],
            ],
        ];
        $this->subscriptionService->createSubscriptionWithSchedule($params);
    }

    /**
     * @throws ApiErrorException
     */
    public function runAnalysis(): void
    {
        //increment by one day to make sure all invoices end up finalized at the end
        $newClockTime = $this->startTime->addDay();
        $endClockTime = $this->startTime->addYear();
        while ($newClockTime < $endClockTime) {
            $newClockTime = $newClockTime->addMonth();
            $clock = $this->clockService->advanceClockAndPollUntilReady($this->stripeTestClockId, $newClockTime);
            Log::info("Advanced Stripe Time Clock: {$clock->name} ({$clock->id})", [
                'frozen_time' => $clock->frozen_time,
                'status' => $clock->status,
            ]);
        }
    }

    /**
     * @throws ApiErrorException
     */
    public function getAnalysisData(): array
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
}