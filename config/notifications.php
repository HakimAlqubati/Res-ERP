<?php

return [
    'handlers' => [
        \App\Services\Warnings\Handlers\LowStockHandler::class,
        // لاحقًا: أضف أنواعًا أخرى هنا فقط
        \App\Services\Warnings\Handlers\MissedCheckinHandler::class,
        // \App\Services\Warnings\Handlers\ExpiredDocumentHandler::class,
        \App\Services\Warnings\Handlers\MaintenanceDueHandler::class, // جديد

    ],
];
