<?php

declare(strict_types=1);

namespace App\Transformers\SubscriptionAnalysis;

use App\DTO\SubscriptionAnalysis\ProductInvoiceAnalysisDTO;
use Carbon\CarbonImmutable;

class ProductInvoiceAnalysisTransformer
{
    public function transformDataToArrayForDisplay(ProductInvoiceAnalysisDTO $productData, int $startTime): array
    {
        $return = [];
        //add one row to display data for each customer
        foreach ($productData->getCustomerData() as $customer) {
            $filledCustomerMonthTotals = $this->fillEmptyMonthsFromStartTime($customer->getMonthTotals(), $startTime);
            $return[] = array_merge([$customer->customerName, $productData->getProductName()], $filledCustomerMonthTotals, [$customer->getOverallTotal()]);
        }
        //add row to display product totals
        $filledProductMonthTotals = $this->fillEmptyMonthsFromStartTime($productData->getProductTotalsByMonth(), $startTime);
        $productTotalsRow = array_merge(['Totals', ''], $filledProductMonthTotals);
        $productTotalsRow[] = $productData->getProductOverallTotal();
        $return[] = $productTotalsRow;

        return $return;
    }

    private function fillEmptyMonthsFromStartTime(array $dataByMonth, int $startTime): array
    {
        //sort by key (timestamp of month's end) to ensure chronological order
        ksort($dataByMonth);
        $filledArray = [];
        $currentMonthTime = CarbonImmutable::createFromTimestamp($startTime);
        //iterate through 12 months
        for ($i = 0; $i < 12; $i++) {
            $currentMonthEndTime = $currentMonthTime->endOfMonth()->getTimestamp();
            if (!isset($dataByMonth[$currentMonthEndTime])) {
                $filledArray[$currentMonthEndTime] = 0;
            } else {
                $filledArray[$currentMonthEndTime] = $dataByMonth[$currentMonthEndTime];
            }
            $currentMonthTime = $currentMonthTime->addMonth();
        }

        return $filledArray;
    }
}