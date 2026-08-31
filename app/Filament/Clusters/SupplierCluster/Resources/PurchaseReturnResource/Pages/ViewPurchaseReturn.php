<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Models\PurchaseReturn;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseReturn extends ViewRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn(): bool => $this->record->status === PurchaseReturn::STATUS_DRAFT && ! $this->record->cancelled),
            PurchaseReturnResource::getApproveAction()
                ->after(fn() => $this->fillForm()),
            PurchaseReturnResource::getCancelAction()
                ->after(fn() => $this->fillForm()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['details'] = $this->record->details()->with('purchaseInvoiceDetail')->get()->map(function ($detail) {
            return [
                'purchase_invoice_detail_id' => $detail->purchase_invoice_detail_id,
                'product_id'                 => $detail->product_id,
                'unit_id'                    => $detail->unit_id,
                'package_size'               => $detail->package_size,
                'purchased_quantity'         => $detail->purchaseInvoiceDetail?->quantity,
                'quantity'                   => $detail->quantity,
                'unit_price'                 => $detail->unit_price,
                'total_price'                => $detail->total_price,
                'notes'                      => $detail->notes,
            ];
        })->toArray();

        return $data;
    }
}
