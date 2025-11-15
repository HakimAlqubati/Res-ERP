<?php

namespace App\Filament\Clusters\POSIntegration\Resources\PosSales\Pages;

use App\Filament\Clusters\POSIntegration\Resources\PosSales\PosSaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosSale extends CreateRecord
{
    protected static string $resource = PosSaleResource::class;
}
