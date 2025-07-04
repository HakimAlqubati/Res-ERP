<?php

namespace App\Traits\Product;

use App\Models\UnitPrice;

trait HasScopedUnitPrices
{


    // Supply-only + All
    public function supplyUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->forSupply()
            ->orderBy('package_size', 'asc');
    }


    public function reportUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->forReportsExcludingManufacturing()
            ->orderBy('package_size', 'asc');
    }


    // Manufacturing-only + All
    public function manufacturingUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->whereIn('usage_scope', [
                UnitPrice::USAGE_ALL,
                UnitPrice::USAGE_MANUFACTURING_ONLY,
            ]);
    }

    // Hidden only
    public function hiddenUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->where('usage_scope', UnitPrice::USAGE_NONE);
    }

    // All without filtering
    public function allUnitPrices()
    {
        return $this->hasMany(UnitPrice::class);
    }

    public function outUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->forOut()
            ->orderBy('package_size', 'asc');
    }

    public function unitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->forOperations()
            ->orderBy('package_size', 'asc');
    }

    public function supplyOutUnitPrices()
    {
        return $this->hasMany(UnitPrice::class)
            ->forSupplyAndOut()
            ->orderBy('package_size', 'asc');
    }
}