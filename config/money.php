<?php

return [
    /*
     |--------------------------------------------------------------------------
     | cknow/laravel-money based on library moneyphp/money
     |--------------------------------------------------------------------------
     */
    'locale' => 'de_DE',
    'defaultCurrency' => 'EUR',
    'defaultFormatter' => \App\Support\Money\DefaultMoneyFormater::class,
    // 'defaultSerializer' => \App\Support\Money\DefaultMoneySerializer::class,
    'defaultSerializer' => null,
    'isoCurrenciesPath' => is_dir(__DIR__.'/../vendor')
        ? __DIR__.'/../vendor/moneyphp/money/resources/currency.php'
        : __DIR__.'/../../../moneyphp/money/resources/currency.php',
    'currencies' => [
        'iso' => 'all',
        'bitcoin' => '',
        'custom' => [
            // 'EUR' => 2,
            // 'MY2' => 3
        ],
    ],
];
