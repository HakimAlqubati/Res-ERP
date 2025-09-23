<?php

namespace App\Filament\Resources\OrderResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ViewRecord;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make(),
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

        $pdf = PDF::loadView('export.order_pdf', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("order_no" . '.pdf');
            }, "order_no" . "_" . $this->record->id . '.pdf');
    }
 

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $order = Order::with('orderDetails')->find($this->record->id);
        if ($order) {
            foreach ($order->orderDetails as $detail) {
                $detail->update([
                    'total_unit_price' => $detail->available_quantity * $detail->price,
                ]);
            }
        }

        return $data;
    }
}
