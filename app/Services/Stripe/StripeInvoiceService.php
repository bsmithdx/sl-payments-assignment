<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Collection;
use Stripe\Invoice;
use Stripe\StripeClient;

class StripeInvoiceService
{
    public function __construct(
        private StripeClient $client,
    )
    {}

    /**
     * @return Collection<Invoice>
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllInvoicesForCustomer(string $customerId): Collection
    {
        $params = [
            'limit' => 100,
            'customer' => $customerId,
            'status' => 'paid',
        ];
        return $this->client->invoices->all($params);
    }

}