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

    public function __construct(
        private readonly CarbonImmutable $startTime,
        private readonly CarbonImmutable $endTime,
    )
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

            $this->info("Advancing the Stripe Clock through the simulation ({$this->startTime->toDateTimeString()} -> {$this->endTime->toDateTimeString()})");
            $this->info('This usually takes about 4 minutes...');
            $analysisService->runAnalysis();

            $this->info('Generating data for analysis');
            $productData = $analysisService->getAnalysisData();

            $this->info('Displaying tables by product');
            $headers = [
                'Customer Email',
                'Product Name',
                $this->startTime->getTranslatedMonthName(),
                $this->startTime->addMonths(1)->getTranslatedMonthName(),
                $this->startTime->addMonths(2)->getTranslatedMonthName(),
                $this->startTime->addMonths(3)->getTranslatedMonthName(),
                $this->startTime->addMonths(4)->getTranslatedMonthName(),
                $this->startTime->addMonths(5)->getTranslatedMonthName(),
                $this->startTime->addMonths(6)->getTranslatedMonthName(),
                $this->startTime->addMonths(7)->getTranslatedMonthName(),
                $this->startTime->addMonths(8)->getTranslatedMonthName(),
                $this->startTime->addMonths(9)->getTranslatedMonthName(),
                $this->startTime->addMonths(10)->getTranslatedMonthName(),
                $this->startTime->addMonths(11)->getTranslatedMonthName(),
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
