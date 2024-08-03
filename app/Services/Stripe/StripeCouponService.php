<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Stripe\Collection;
use Stripe\Coupon;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeCouponService
{
    public function __construct(
        private readonly StripeClient $client,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function getAllCoupons(): Collection
    {
        return $this->client->coupons->all();
    }

    /**
     * @throws ApiErrorException
     */
    public function getCouponByName(string $name): ?Coupon
    {
        $coupons = $this->getAllCoupons();
        foreach ($coupons as $coupon) {
            if ($coupon->name === $name) {
                return $coupon;
            }
        }
        return null;
    }
}