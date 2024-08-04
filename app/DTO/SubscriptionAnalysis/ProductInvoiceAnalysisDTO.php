<?php

declare(strict_types=1);

namespace App\DTO\SubscriptionAnalysis;

use App\Util\ExchangeRate;
use Carbon\CarbonImmutable;
use Stripe\Invoice;

class ProductInvoiceAnalysisDTO
{
    /** @var array<CustomerInvoiceAnalysisDTO> $customerData */
    private array $customerData = [];
    private array $productTotalsByMonth = [];
    private int $productOverallTotal = 0;
    public function __construct(
        private readonly string $productId,
        private readonly string $productName,
    )
    {}
    public function getProductName(): string
    {
        return $this->productName;
    }
    public function getCustomerData(): array
    {
        return $this->customerData;
    }

    public function getProductTotalsByMonth(): array
    {
        return $this->productTotalsByMonth;
    }

    public function getProductOverallTotal(): int
    {
        return $this->productOverallTotal;
    }

    /**
     * @throws \Exception
     */
    public function addInvoiceData(Invoice $invoice): void
    {
        //setting key to int value of month so need to map to month name on display since not necessarily in order i.e. 1-12
        $monthEndTimestamp = CarbonImmutable::createFromTimestamp($invoice->created)->endOfMonth()->getTimestamp();
        $convertedAmount = $this->convertAmountToUsd($invoice->currency, $invoice->amount_paid);
        if (!isset($this->customerData[$invoice->customer])) {
            $this->customerData[$invoice->customer] = new CustomerInvoiceAnalysisDTO($invoice->customer, $invoice->customer_name);
        }
        //handle updating customer monthly and total amounts
        $this->customerData[$invoice->customer]->addAmountForTimestamp($convertedAmount, $monthEndTimestamp);

        //Handle updating total amounts for product
        if (!isset($this->productTotalsByMonth[$monthEndTimestamp])) {
            $this->productTotalsByMonth[$monthEndTimestamp] = $convertedAmount;
        } else {
            $this->productTotalsByMonth[$monthEndTimestamp] += $convertedAmount;
        }
        $this->productOverallTotal += $convertedAmount;
    }

    public function convertAmountToUsd(string $currency, int $amount): int
    {
        //TODO: pull in moneyphp library to handle currency conversion through injected Converter class (avoids dealing with floats)
        switch (strtoupper($currency)) {
            case 'USD': return (int) round($amount);
            case 'GBP': return (int) round(ExchangeRate::$GBP_TO_USD * $amount);
            case 'EUR': return (int) round(ExchangeRate::$EUR_TO_USD * $amount);
            default: throw new \Exception('Unsupported currency used');
        }
    }
}