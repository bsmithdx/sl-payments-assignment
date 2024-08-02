<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCustomerService
{
    public function __construct(
        private readonly StripeClient $client,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(
        string $name,
        string $email,
        ?string $defaultPaymentMethod = null,
        ?string $testClockId = null
    ): Customer
    {
        $params = [
            'name' => $name,
            'email' => $email,
        ];
        if ($defaultPaymentMethod) {
            $params['default_payment_method'] = $defaultPaymentMethod;
        }
        if ($testClockId) {
            $params['test_clock'] = $testClockId;
        }
        return $this->client->customers->create($params);
    }
}