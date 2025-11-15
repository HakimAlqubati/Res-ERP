<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosSales\PosSaleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPosSale extends ViewRecord
{
    protected static string $resource = PosSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
