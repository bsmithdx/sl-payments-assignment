<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SubscriptionAnalysis\SubscriptionAnalysisService;
use App\Transformers\SubscriptionAnalysis\ProductInvoiceAnalysisTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class GenerateSubscriptionAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-subscription-analysis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run analysis on Stripe subscription data and produce a report showing projected revenue by product over 12 months';

    public function __construct( private readonly CarbonImmutable $startTime)
    {
        parent::__construct();
    }
    /**
     * Execute the console command.
     */
    public function handle(SubscriptionAnalysisService $analysisService, ProductInvoiceAnalysisTransformer $transformer)
    {

        try {
            $this->info('Adding new Customer and Subscription data to Stripe');
            $analysisService->addDataToStripeBeforeAnalysis();

            $this->info('Advancing the Stripe Clock through the simulation');
            $analysisService->runAnalysis();

            $this->info('Generating data for analysis');
            $productData = $analysisService->getAnalysisData();

            $this->info('Displaying tables by product');
            $headers = [
              'Customer Email',
              'Product Name',
              'Month 1',
              'Month 2',
              'Month 3',
              'Month 4',
              'Month 5',
              'Month 6',
              'Month 7',
              'Month 8',
              'Month 9',
              'Month 10',
              'Month 11',
              'Month 12',
              'Life Time Value',
            ];

            foreach ($productData as $productDatum) {
                $transformedData = $transformer->transformDataToArrayForDisplay($productDatum, $this->startTime->getTimestamp());
                $this->table($headers, $transformedData);
            }
        } catch (ApiErrorException $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->error('There was an error with the Stripe API (see logs for details)');
        } catch (\Exception $exception) {
            Log::error($exception->getMessage(), ['exception' => $exception]);
            $this->error('There was an error running this command (see logs for details)');
        }

    }
}
