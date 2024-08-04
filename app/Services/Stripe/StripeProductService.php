<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeProductService
{
    public function __construct(
        private readonly StripeClient $client,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function getAllProducts(): Collection
    {
        return $this->client->products->all();
    }
}