<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\TestHelpers\TestClock;

class StripeTestClockService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly int $initialTimeout,
        private readonly int $backoffIncrement,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    private function advanceClock(string $clockId, int $timestamp): TestClock
    {
        return $this->stripeClient->testHelpers->testClocks->advance($clockId, [
            'frozen_time' => $timestamp,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    private function getClock(string $clockId): TestClock
    {
        return $this->stripeClient->testHelpers->testClocks->retrieve($clockId);
    }

    /**
     * @throws ApiErrorException
     */
    public function advanceClockAndWait(string $clockId, int $timestamp): TestClock
    {
        $currentTimeout = $this->initialTimeout;
        try {
            $clock = $this->advanceClock($clockId, $timestamp);
            //use an incrementing backoff time to poll the test clock until it's in a "ready" state after advancing
            while ($clock->status !== 'ready') {
                sleep($currentTimeout);
                $clock = $this->getClock($clockId);
                $currentTimeout = $currentTimeout + $this->backoffIncrement;
            }
            //return the clock with a 'ready' state
            return $clock;
        } catch(ApiErrorException $exception) {
            Log::error('Stripe Time Clock Exception: ' . $exception->getMessage(), ['exception' => $exception]);
            throw $exception;
        }

    }
}