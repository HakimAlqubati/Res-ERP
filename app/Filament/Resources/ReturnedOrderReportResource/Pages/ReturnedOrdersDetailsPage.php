<?php

namespace App\Filament\Resources\ReturnedOrderReportResource\Pages;

use App\Filament\Resources\ReturnedOrderReportResource;
use App\Models\ReturnedOrder;
use App\Services\Orders\Reports\ReturnedOrdersReportService;
use Filament\Actions;
use Filament\Resources\Pages\Page;

class ReturnedOrdersDetailsPage extends Page
{
    protected static string $resource = ReturnedOrderReportResource::class;
    protected string $view = 'filament.pages.order-reports.returned-report-details';
    public ?ReturnedOrder $order = null;

    public function mount(int $id): void
    {
        $this->order = ReturnedOrder::with(['details.product', 'details.unit'])->findOrFail($id);
    }

    public function getViewData(): array
    {
        return [
            'order' => $this->order,
        ];
    }
}
