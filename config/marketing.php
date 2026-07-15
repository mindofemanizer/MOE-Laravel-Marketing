<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    */

    'models' => [

        'user' => App\Models\User::class,

        'order' => App\Models\Order::class,

        'order_item' => App\Models\OrderItem::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */

    'tables' => [

        'commission_ledger' => 'commission_ledger',

        'attribution_logs' => 'marketing_attribution_logs',

        'promos' => 'promos',

        'promo_usages' => 'promo_usages',

    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Settings
    |--------------------------------------------------------------------------
    */

    'commission' => [

        'default_rate' => env('COMMISSION_DEFAULT_RATE', '10'),

        'hold_days' => env('COMMISSION_HOLD_DAYS', 7),

        'min_withdraw' => env('COMMISSION_MIN_WITHDRAW', 50000),

        'enabled' => env('COMMISSION_ENABLED', true),

    ],

];
