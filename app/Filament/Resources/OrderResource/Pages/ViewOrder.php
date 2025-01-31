<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ViewRecord;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\EditAction;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('Export to Excel')->label(__('lang.export_excel'))
                ->action('exportToExcel'),
            Action::make('Export to PDF')->label(__('lang.export_pdf'))
                ->action('exportToPdf')
                ->color('success'),
        ];
    }

    public function exportToExcel()
    {
        return redirect('orders/export/' . $this->record->id);
    }
    public function exportToPdf()
    {
        $order = Order::find($this->record->id);
        $orderDetails = $order?->orderDetails;
         
        $data = [
            'order' => $order,
            'orderDetails' => $orderDetails,
        ];
        
        $pdf = Pdf::loadView('export.order_pdf', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("order_no" . '.pdf');
            }, "order_no" . "_" . $this->record->id . '.pdf');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // $data['customer_id'] = $this?->record?->customer?->name;
        // $data['branch_id'] = $this?->record?->branch?->name;
        return $data;
    }
}
