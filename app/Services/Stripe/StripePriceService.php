<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripePriceService
{
    public function __construct(
        private readonly StripeClient $client,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function getPricesByLookupKeys(array $lookupKeys): Collection
    {
        return $this->client->prices->all([
            'lookup_keys' => $lookupKeys,
        ]);
    }
}