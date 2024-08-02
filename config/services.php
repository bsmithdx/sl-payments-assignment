<?php

return [
    'stripe' => [
        'secret' => env('STRIPE_API_KEY'),
        'test_clock' => [
            'id' => env('STRIPE_TEST_CLOCK'),
            'initial_timeout' => 1,
            'backoff_increment' => 1,
            'max_attempts' => 10,
        ],
        'subscription_analysis' => [
            'new_subscription_coupon_id' => env('STRIPE_SUBSCRIPTION_ANALYSIS_NEW_COUPON_ID'),
            'new_subscription_price_id' => env('STRIPE_SUBSCRIPTION_ANALYSIS_NEW_PRICE_ID'),
            'upgrade_subscription_price_id' => env('STRIPE_SUBSCRIPTION_ANALYSIS_UPGRADE_PRICE_ID'),
        ]
    ],
];
