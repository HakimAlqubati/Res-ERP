<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Branch;
use App\Models\OrderDetails;
use App\Models\UnitPrice;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['customer_id'] = Branch::find($data['branch_id'])->manager_id ?? null;

        // dd($data);
        return $data;
    }

    protected function afterCreate(): void {}
}
