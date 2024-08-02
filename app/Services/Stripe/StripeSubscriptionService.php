<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription;

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
}