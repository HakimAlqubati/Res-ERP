<?php
namespace App\Filament\Resources\OrderDeliveryReportResource\Pages;

use App\Filament\Resources\OrderDeliveryReportResource;
use App\Services\Reports\ResellerBranches\OrderDeliveryReportService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class OrderDeliveryReportPage extends Page
{
    protected static string $resource = OrderDeliveryReportResource::class;

    protected static string $view = 'filament.resources.order-delivery-report-resource.pages.report-view';

    public Collection $report;

    public function mount(): void
    {
        $this->report = (new OrderDeliveryReportService())->generate();
        dd($this->report);
    }
}