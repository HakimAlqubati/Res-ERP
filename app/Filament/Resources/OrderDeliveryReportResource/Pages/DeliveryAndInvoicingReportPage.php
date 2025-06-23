<?php

namespace App\Filament\Resources\OrderDeliveryReportResource\Pages;

use App\Filament\Resources\OrderDeliveryReportResource;
use App\Services\Reports\ResellerBranches\OrderDeliveryReportService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class DeliveryAndInvoicingReportPage extends Page
{
    protected static string $resource = OrderDeliveryReportResource::class;

    protected static string $view = 'filament.resources.order-delivery-report-resource.pages.delivery-and-invoicing';

    public Collection $report;

    public function mount(): void
    {
        $this->report = (new OrderDeliveryReportService())->generate();
    }

    public  function getTitle(): string
    {
        return 'Delivery & Invoicing Report';
    }
}
