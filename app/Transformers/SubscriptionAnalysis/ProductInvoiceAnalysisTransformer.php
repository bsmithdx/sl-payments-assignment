<?php

declare(strict_types=1);

namespace App\Transformers\SubscriptionAnalysis;

use App\DTO\SubscriptionAnalysis\ProductInvoiceAnalysisDTO;

class ProductInvoiceAnalysisTransformer
{
    public function transformDataToArrayForDisplay(ProductInvoiceAnalysisDTO $productData, int $startTime): array
    {
        //TODO: might need to handle adding empty months to fill out table if not present
        $return = [];
        //add one row to display data for each customer
        foreach ($productData->getCustomerData() as $customer) {
            $customerMonthTotals = $customer->getMonthTotals();
            //sort by key (timestamp of month's end) to ensure chronological order
            ksort($customerMonthTotals);
            $return[] = array_merge([$customer->customerName, $productData->getProductName()], $customerMonthTotals, [$customer->getOverallTotal()]);
        }
        //add row to display product totals
        $productMonthTotals = $productData->getProductTotalsByMonth();
        //sort by key (timestamp of month's end) to ensure chronological order
        ksort($productMonthTotals);
        $productTotalsRow = array_merge(['Totals', ''], $productMonthTotals);
        $productTotalsRow[] = $productData->getProductOverallTotal();
        $return[] = $productTotalsRow;

        return $return;
    }
}