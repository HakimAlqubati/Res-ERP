<?php

return [
    'handlers' => [
        \App\Services\Warnings\Handlers\LowStockHandler::class,
        // لاحقًا: أضف أنواعًا أخرى هنا فقط
        \App\Services\Warnings\Handlers\MissedCheckinHandler::class,
        // \App\Services\Warnings\Handlers\ExpiredDocumentHandler::class,
        \App\Services\Warnings\Handlers\MaintenanceDueHandler::class, // جديد

    ],

    /*
    |--------------------------------------------------------------------------
    | Warning Channels Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which channels should be used for sending warnings.
    | Each channel can be enabled/disabled independently.
    |
    */
    'channels' => [
        'database' => [
            'enabled' => true,
        ],
        'email' => [
            'enabled' => env('WARNING_EMAIL_ENABLED', false),
        ],
        // Future channels:
        // 'fcm' => [
        //     'enabled' => env('WARNING_FCM_ENABLED', false),
        // ],
        // 'sms' => [
        //     'enabled' => env('WARNING_SMS_ENABLED', false),
        // ],
    ],
];
