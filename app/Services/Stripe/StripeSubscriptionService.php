<?php

declare(strict_types=1);

namespace App\Services\Stripe;

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
        ?int $trialPeriodDays = null,
        ?string $currency = null,
    ): Subscription
    {
        $params = [
            'customer' => $customerId,
            'items' => [
                [
                    'price' => $priceId,
                ],
            ],
        ];
        if ($couponId) {
            $params['coupon'] = $couponId;
        }
        if ($trialPeriodDays) {
            $params['trial_period_days'] = $trialPeriodDays;
        }
        if ($currency) {
            $params['currency'] = $currency;
        }
        return $this->client->subscriptions->create($params);
    }

    /**
     * @throws ApiErrorException
     */
    public function createSubscriptionWithSchedule(array $params): SubscriptionSchedule
    {

        return $this->client->subscriptionSchedules->create($params);
    }
}