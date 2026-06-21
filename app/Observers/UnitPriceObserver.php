<?php

namespace App\Observers;

use App\Models\UnitPrice;
use App\Services\UpdateCompositeProductsFromUnitPriceService;

class UnitPriceObserver
{
    public function updated(UnitPrice $unitPrice): void
    {
        if (! $unitPrice->wasChanged('price')) {
            return;
        }

        // app(UpdateCompositeProductsFromUnitPriceService::class)->handle(
        //     $unitPrice->id,
        //     $unitPrice->product_id,
        //     $unitPrice->unit_id,
        //     (float) $unitPrice->price
        // );
    }
}
