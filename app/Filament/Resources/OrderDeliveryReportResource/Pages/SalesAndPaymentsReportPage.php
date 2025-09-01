<?php

namespace App\Filament\Resources\OrderDeliveryReportResource\Pages;

use App\Filament\Resources\OrderDeliveryReportResource;
use App\Services\Reports\ResellerBranches\BranchSalesBalanceReportService;
use App\Services\Reports\ResellerBranches\OrderSalesPaymentsReportService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class SalesAndPaymentsReportPage extends Page
{
    protected static string $resource = OrderDeliveryReportResource::class;

    protected string $view = 'filament.resources.order-delivery-report-resource.pages.sales-and-payments';

    public Collection $report;

    public function mount(): void
    {
        $reportData = (new BranchSalesBalanceReportService())->generate();
        
        $this->report = $reportData;
    }

    public function getTitle(): string
    {
        return 'Sales & Payments Report';
    }
}