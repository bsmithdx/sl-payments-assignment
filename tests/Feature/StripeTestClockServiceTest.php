<?php

namespace Tests\Feature;

use App\Services\Stripe\StripeTestClockService;
use Carbon\CarbonImmutable;
use Tests\TestCase;
use Mockery;
use Stripe\StripeClient;
use Stripe\TestHelpers\TestClock;

class StripeTestClockServiceTest extends TestCase
{
    public function testAdvanceClockAndPollUntilReady(): void
    {
        $stripeClient = Mockery::mock(StripeClient::class);
        $partialService = Mockery::mock(StripeTestClockService::class, [
            $stripeClient,
            1,
            1,
            5,
        ])->makePartial();

        $testClock1 = new TestClock();
        $testClock1->frozen_time = 1709301600;
        $testClock1->status = 'advancing';
        //should call method to advance clock once
        $partialService->shouldReceive('advanceClock')
            ->once()
            ->with('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ', 1709301600)
            ->andReturn($testClock1);

        $testClock2 = new TestClock();
        $testClock2->frozen_time = 1709301600;
        $testClock2->status = 'ready';
        //should call method to poll clock and return 'ready' status 3 times
        $partialService->shouldReceive('getClock')
            ->times(3)
            ->with('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ')
            ->andReturnValues([
                $testClock1,
                $testClock1,
                $testClock2,
            ]);

        $clock = $partialService->advanceClockAndPollUntilReady('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ', CarbonImmutable::createFromTimestamp(1709301600));
        $this->assertSame('ready', $clock->status);
        $this->assertSame(1709301600, $clock->frozen_time);
    }

    public function testAdvanceClockAndPollUntilReadyMaxAttempts(): void
    {
        $stripeClient = Mockery::mock(StripeClient::class);
        $partialService = Mockery::mock(StripeTestClockService::class, [
            $stripeClient,
            1,
            1,
            2,
        ])->makePartial();

        $testClock1 = new TestClock();
        $testClock1->frozen_time = 1709301600;
        $testClock1->status = 'advancing';
        //should call method to advance clock once
        $partialService->shouldReceive('advanceClock')
            ->once()
            ->with('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ', 1709301600)
            ->andReturn($testClock1);
        //should call method to poll clock and return 'ready' status 2 times
        $partialService->shouldReceive('getClock')
            ->times(2)
            ->with('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ')
            ->andReturnValues([
                $testClock1,
                $testClock1,
            ]);
        $this->expectExceptionMessage('StripeTestClockService: max attempts exceeded');
        $partialService->advanceClockAndPollUntilReady('clock_1Pj6RjCeQ4FW3OFGGeVNFrrZ', CarbonImmutable::createFromTimestamp(1709301600));
    }
}
