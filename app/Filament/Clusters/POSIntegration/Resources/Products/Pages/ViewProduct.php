<?php

namespace App\Filament\Clusters\POSIntegration\Resources\Products\Pages;

use App\Filament\Clusters\POSIntegration\Resources\Products\ProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
