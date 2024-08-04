<?php

namespace App\DTO\SubscriptionAnalysis;

use Carbon\CarbonImmutable;
use Stripe\Invoice;

class ProductInvoiceAnalysisDTO
{
    /** @var array<CustomerInvoiceAnalysisDTO> $customerData */
    private array $customerData = [];
    private array $productTotalsByMonth = [];
    private int $productOverallTotal = 0;
    public function __construct(
        public readonly string $productId,
        public readonly string $productName,
    )
    {}

    public function addInvoiceData(Invoice $invoice): void
    {
        //setting key to int value of month so need to map to month name on display since not necessarily in order i.e. 1-12
        $monthEndTimestamp = CarbonImmutable::createFromTimestamp($invoice->created)->endOfMonth()->getTimestamp();
        $convertedAmount = strtolower($invoice->currency) !== 'usd' ? $this->convertAmountToUsd($invoice->currency, $invoice->amount_paid) : $invoice->amount_paid;
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
        //TODO: handle currency conversion
        return $amount;
    }

    public function toArrayForTableDisplay(): array
    {
        //TODO: might need to handle adding empty months to fill out table if not present
        $return = [];
        //add one row to display data for each customer
        /** @var CustomerInvoiceAnalysisDTO $customer */
        foreach ($this->customerData as $customer) {
            $customerMonthTotals = $customer->getMonthTotals();
            //sort by key (timestamp of month's end) to ensure chronological order
            ksort($customerMonthTotals);
            $return[] = array_merge([$customer->customerName, $this->productName], $customer->getMonthTotals(), [$customer->getOverallTotal()]);
        }
        //add row to display product totals
        $productMonthTotals = $this->productTotalsByMonth;
        //sort by key (timestamp of month's end) to ensure chronological order
        ksort($productMonthTotals);
        $productTotalsRow = array_merge(['Totals', ''], $productMonthTotals);
        $productTotalsRow[] = $this->productOverallTotal;
        $return[] = $productTotalsRow;

        return $return;

    }
}