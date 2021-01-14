<?php

return [
    // Mode for GIDX service: sandbox or live.
    'mode' => env('GIDX_MODE', 'sandbox'),
    'sandbox' => [
        'api_key' => env('GIDX_SANDBOX_API_KEY'),
        'merchant_id' => env('GIDX_SANDBOX_MERCHANT_ID'),
    ],
    'live' => [
        'api_key' => env('GIDX_LIVE_API_KEY'),
        'merchant_id' => env('GIDX_LIVE_MERCHANT_ID'),
    ],
    // Base uri for GIDX apis.
    'base_uri' => 'https://api.gidx-service.in',
    'product_type_id' => env('GIDX_PRODUCT_TYPE_ID'),
    'device_type_id' => env('GIDX_DEVICE_TYPE_ID'),
    'activity_type_id' => env('GIDX_ACTIVITY_TYPE_ID'),
    'callback_url' => env('GIDX_CALLBACK_URL', 'https://api.example.com/api/tsevo/callback'),
];
