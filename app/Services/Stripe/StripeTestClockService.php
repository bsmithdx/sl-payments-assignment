<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Stripe\Collection;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\TestHelpers\TestClock;

class StripeTestClockService
{
    public function __construct(
        private readonly StripeClient $stripeClient,
        private readonly int $initialTimeout,
        private readonly int $backoffIncrement,
        private readonly int $maxAttempts,
    )
    {}

    /**
     * @throws ApiErrorException
     */
    public function advanceClock(string $clockId, int $timestamp): TestClock
    {
        return $this->stripeClient->testHelpers->testClocks->advance($clockId, [
            'frozen_time' => $timestamp,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function getClock(string $clockId): TestClock
    {
        return $this->stripeClient->testHelpers->testClocks->retrieve($clockId);
    }

    /**
     * @throws ApiErrorException
     */
    public function getAllClocks(): Collection
    {
        return $this->stripeClient->testHelpers->testClocks->all();
    }

    /**
     * @throws ApiErrorException
     * @throws \Exception
     */
    public function advanceClockAndPollUntilReady(string $clockId, CarbonImmutable $newTime): TestClock
    {
        $attempts = 0;
        $currentTimeout = $this->initialTimeout;
        try {
            $clock = $this->advanceClock($clockId, $newTime->getTimestamp());
            //use an incrementing backoff time to poll the test clock until it's in a "ready" state after advancing
            while ($clock->status !== 'ready') {
                //throw an exception if we hit the configured maximum number of attempts to poll for 'ready' clock
                if ($attempts++ >= $this->maxAttempts) {
                    Log::error('Max attempts reached while polling Stripe Test Clock');
                    //TODO: create custom exception
                    throw new \Exception('StripeTestClockService: max attempts exceeded');
                }
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