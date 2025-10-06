<?php

namespace App\Filament\Resources\DeliveredResellerOrdersResource\Pages;

use App\Filament\Resources\DeliveredResellerOrdersResource;
use App\Models\Branch;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveredResellerOrder extends CreateRecord
{
    protected static string $resource = DeliveredResellerOrdersResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // set manager/customer based on selected branch (reseller)
        $data['customer_id'] = Branch::find($data['branch_id'])->manager_id ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        // optional: show notification or redirect
    }
}
