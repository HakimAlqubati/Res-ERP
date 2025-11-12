<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Categories\Pages;

use App\Filament\Clusters\POSIntegration\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['for_pos'] = 1;
        return $data;
    }
}
