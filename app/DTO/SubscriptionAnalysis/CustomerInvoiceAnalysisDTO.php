<?php

declare(strict_types=1);

namespace App\DTO\SubscriptionAnalysis;

class CustomerInvoiceAnalysisDTO
{
    private int $overallTotal = 0;
    private array $timestampTotals = [];
    public function __construct(
        public string $customerId,
        public readonly string $customerName,
    )
    {}

    public function getOverallTotal(): int
    {
        return $this->overallTotal;
    }

    private function updateOverallTotal(int $amount): void
    {
        $this->overallTotal += $amount;
    }

    public function getMonthTotals(): array
    {
        return $this->timestampTotals;
    }

    public function addAmountForTimestamp(int $amount, int $timestamp): void
    {
        if (!isset($this->timestampTotals[$timestamp])) {
            $this->timestampTotals[$timestamp] = $amount;
        } else {
            $this->timestampTotals[$timestamp] += $amount;
        }
        $this->updateOverallTotal($amount);
    }
}