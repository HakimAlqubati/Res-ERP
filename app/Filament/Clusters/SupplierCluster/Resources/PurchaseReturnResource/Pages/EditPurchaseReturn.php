<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Modules\Stock\PurchaseReturns\Actions\CreatePurchaseReturnDraftAction;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EditPurchaseReturn extends EditRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PurchaseReturnResource::getApproveAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
            PurchaseReturnResource::getCancelAction()
                ->after(fn() => $this->redirect(PurchaseReturnResource::getUrl('view', ['record' => $this->record]))),
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $rawState = $this->form->getRawState();
        $data['details'] = $rawState['details'] ?? $data['details'] ?? [];

        $action = app(CreatePurchaseReturnDraftAction::class);
        $dto = CreatePurchaseReturnDTO::fromRequest($data, (int) auth()->id(), (int) $record->id);

        try {
            return $action->execute($dto);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Validation Failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
