<?php
namespace App\Filament\Resources\ResellerSaleResource\Pages;

use App\Filament\Resources\ResellerSaleResource;
use App\Models\ResellerSale;
use Filament\Resources\Pages\Page;

class PrintResellerInvoice extends Page
{
    protected static string $resource = ResellerSaleResource::class;

    protected static string $view = 'filament.pages.stock-report.print-reseller-invoice';
    public ResellerSale $record;

    public function mount($record): void
    {
     
        $this->record = $record;;
    }
}