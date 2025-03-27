<?php

namespace App\Filament\Resources\PurchaseInvoiceResource\Pages;

use App\Filament\Resources\PurchaseInvoiceResource;
use Filament\Resources\Pages\Page;

class OcrAnalyzer extends Page
{
    protected static string $resource = PurchaseInvoiceResource::class;

    protected static string $view = 'filament.resources.purchase-invoice-resource.pages.ocr-analyzer';
}
