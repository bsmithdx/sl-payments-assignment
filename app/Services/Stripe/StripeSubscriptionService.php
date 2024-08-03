<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Carbon\CarbonImmutable;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\SubscriptionSchedule;

class StripeSubscriptionService
{
    public function __construct(
        private readonly StripeClient $client,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function createSubscription(
        string $customerId,
        //only supporting a single price for now
        string $priceId,
        ?string $couponId = null,
        ?int $trialDays = null,
        ?string $currency = null,
    ): Subscription
    {
        $params = [
            'customer' => $customerId,
            'items' => [
                'price' => $priceId
            ],
            //TODO: test if this parameter is required or not (either here or on the customer)
            //'default_payment_method' => 'pm_card_visa',
        ];
        if ($couponId) {
            $params['coupon'] = $couponId;
        }
        if ($trialDays) {
            $params['trial_days'] = $trialDays;
        }
        if ($currency) {
            $params['currency'] = $currency;
        }
        return $this->client->subscriptions->create($params);
    }

    /**
     * @throws ApiErrorException
     */
    public function upgradeExistingSubscriptionWithSchedule(string $subscriptionId, string $newPriceId, CarbonImmutable $upgradeStartTime): SubscriptionSchedule
    {
        $schedule = $this->client->subscriptionSchedules->create([
            'from_subscription' => $subscriptionId,
        ]);

        $params = [
            'phases' => [
                [
                   'items' => [
                       [
                           'price' => $schedule->phases[0]->items[0]->price,
                           'quantity' => $schedule->phases[0]->items[0]->quantity,
                       ],
                   ],
                    'start_date' => $schedule->phases[0]->start_date,
                    'end_date' => $upgradeStartTime->getTimestamp(),
                ],
                [
                    'proration_behavior' => 'always_invoice',
                    'start_date' => $upgradeStartTime->getTimestamp(),
                    'items' => [
                        [
                            'price' => $newPriceId,
                            'quantity' => $schedule->phases[0]->items[0]->quantity,
                        ],
                    ],
                    'collection_method' => 'charge_automatically',
                ]
            ]
        ];
        return $this->client->subscriptionSchedules->update($schedule->id, $params);
    }
}