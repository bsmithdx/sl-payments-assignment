<?php

return [
    'stripe' => [
        'secret' => env('STRIPE_API_KEY'),
        'test_clock' => [
            'id' => env('STRIPE_TEST_CLOCK'),
            'initial_timeout' => 1,
            'backoff_increment' => 1,
        ],
    ],
];
