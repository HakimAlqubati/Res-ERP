<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products\Pages;

use App\Filament\Clusters\POSIntegration\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Product::TYPE_FINISHED_POS;
        return $data;
    }
}
