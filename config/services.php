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
            'start_time' => (int) env('ANALYSIS_START_TIME'),
        ]
    ],
];
