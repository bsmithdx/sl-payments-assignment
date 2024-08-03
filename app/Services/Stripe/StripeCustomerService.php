<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Collection;
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
        ?string $paymentMethod = null,
        ?string $testClockId = null
    ): Customer
    {
        $params = [
            'name' => $name,
            'email' => $email,
        ];
        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
            $params['invoice_settings'] = ['default_payment_method' => $paymentMethod];
        }
        if ($testClockId) {
            $params['test_clock'] = $testClockId;
        }
        return $this->client->customers->create($params);
    }

    /**
     * @return Collection<Customer>
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllCustomers(?string $testClockId = null): Collection
    {
        $params = [
            'limit' => 100,
        ];
        if ($testClockId) {
            $params['test_clock'] = $testClockId;
        }
        return $this->client->customers->all($params);
    }
}