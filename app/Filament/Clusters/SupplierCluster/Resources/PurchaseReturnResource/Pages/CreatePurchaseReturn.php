<?php

namespace App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource\Pages;

use App\Filament\Clusters\SupplierCluster\Resources\PurchaseReturnResource;
use App\Modules\Stock\PurchaseReturns\Actions\CreatePurchaseReturnDraftAction;
use App\Modules\Stock\PurchaseReturns\DataTransferObjects\CreatePurchaseReturnDTO;
use App\Modules\Stock\PurchaseReturns\Queries\GetInvoiceReturnableItemsQuery;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseReturn extends CreateRecord
{
    protected static string $resource = PurchaseReturnResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $invoiceId = request()->query('purchase_invoice_id');
        if ($invoiceId) {
            $query = app(GetInvoiceReturnableItemsQuery::class);
            $invoiceData = $query->execute((int) $invoiceId);

            $data['purchase_invoice_id'] = $invoiceData['invoice_id'];
            $data['supplier_id']         = $invoiceData['supplier_id'];
            $data['store_id']            = $invoiceData['store_id'];
            $data['return_date']         = date('Y-m-d');
            $data['details']             = $invoiceData['items'];
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $action = app(CreatePurchaseReturnDraftAction::class);
        $dto = CreatePurchaseReturnDTO::fromRequest($data, (int) auth()->id());

        return $action->execute($dto);
    }
}
