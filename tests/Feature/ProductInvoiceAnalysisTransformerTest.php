<?php

namespace Tests\Feature;

use App\DTO\SubscriptionAnalysis\ProductInvoiceAnalysisDTO;
use App\Transformers\SubscriptionAnalysis\ProductInvoiceAnalysisTransformer;
use Carbon\CarbonImmutable;
use Stripe\Invoice;
use Tests\TestCase;

class ProductInvoiceAnalysisTransformerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testArrayOutput(): void
    {
        $invoice1 = new Invoice();
        $invoice1->amount_paid = 2000;
        $invoice1->currency = 'usd';
        $invoice1->created = CarbonImmutable::parse('February 2nd, 2024 at 12PM UTC')->getTimestamp();
        $invoice1->customer = 'cus_NeZwdNtLEOXuvB';
        $invoice1->customer_name = 'Brendan Smith';

        $invoice2 = new Invoice();
        $invoice2->amount_paid = 1000;
        $invoice2->currency = 'usd';
        $invoice2->created = CarbonImmutable::parse('March 1st, 2024 at 12PM UTC')->getTimestamp();
        $invoice2->customer = 'cus_NeZwdNtLEOFuiJ';
        $invoice2->customer_name = 'Jane Doe';

        $invoice3 = new Invoice();
        $invoice3->amount_paid = 3500;
        $invoice3->currency = 'usd';
        $invoice3->created = CarbonImmutable::parse('October 3rd, 2024 at 12PM UTC')->getTimestamp();
        $invoice3->customer = 'cus_NeZwdNtLEOXuvB';
        $invoice3->customer_name = 'Brendan Smith';

        $invoice4 = new Invoice();
        $invoice4->amount_paid = 3000;
        $invoice4->currency = 'usd';
        $invoice4->created = CarbonImmutable::parse('December 1st, 2024 at 12PM UTC')->getTimestamp();
        $invoice4->customer = 'cus_NeZwdNtLEOFuiJ';
        $invoice4->customer_name = 'Jane Doe';

        $invoice5 = new Invoice();
        $invoice5->amount_paid = 500;
        $invoice5->currency = 'usd';
        $invoice5->created = CarbonImmutable::parse('January 1st, 2025 at 12PM UTC')->getTimestamp();
        $invoice5->customer = 'cus_NeZwdNtLEOFuiJ';
        $invoice5->customer_name = 'Jane Doe';

        $invoice6 = new Invoice();
        $invoice6->amount_paid = 500;
        $invoice6->currency = 'usd';
        $invoice6->created = CarbonImmutable::parse('February 15th, 2024 at 12PM UTC')->getTimestamp();
        $invoice6->customer = 'cus_NeZwdNtLEOXuvB';
        $invoice6->customer_name = 'Brendan Smith';

        $invoice7 = new Invoice();
        $invoice7->amount_paid = 1500;
        $invoice7->currency = 'usd';
        $invoice7->created = CarbonImmutable::parse('April 2rd, 2024 at 12PM UTC')->getTimestamp();
        $invoice7->customer = 'cus_NeZwdNtLEOXuvB';
        $invoice7->customer_name = 'Brendan Smith';

        $data = new ProductInvoiceAnalysisDTO('product_id', 'Product Name');
        $data->addInvoiceData($invoice1);
        $data->addInvoiceData($invoice2);
        $data->addInvoiceData($invoice3);
        $data->addInvoiceData($invoice4);
        $data->addInvoiceData($invoice5);
        $data->addInvoiceData($invoice6);
        $data->addInvoiceData($invoice7);

        /** @var ProductInvoiceAnalysisTransformer $transformer */
        $transformer = app(ProductInvoiceAnalysisTransformer::class);

        $outputArray = $transformer->transformDataToArrayForDisplay($data, CarbonImmutable::parse('February 2nd, 2024 at 12PM UTC')->getTimestamp());
        $this->assertSame([
            [
                'Brendan Smith',
                'Product Name',
                2500,
                0,
                1500,
                0,
                0,
                0,
                0,
                0,
                3500,
                0,
                0,
                0,
                7500,
            ],
            [
                'Jane Doe',
                'Product Name',
                0,
                1000,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                3000,
                500,
                4500,
            ],
            [
                'Totals',
                '',
                2500,
                1000,
                1500,
                0,
                0,
                0,
                0,
                0,
                3500,
                0,
                3000,
                500,
                12000,
            ],
        ], $outputArray);
    }
}
