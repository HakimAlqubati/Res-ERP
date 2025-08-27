<?php

namespace App\Models\Branch\Traits;

use App\Models\BranchArea;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\Location;
use App\Models\Order;
use App\Models\ResellerSale;
use App\Models\ResellerSaleItem;
use App\Models\ResellerSalePaidAmount;
use App\Models\Store;
use App\Models\User;

trait BranchRelations
{
    public function user()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function areas()
    {
        return $this->hasMany(BranchArea::class);
    }

    public function equipments()
    {
        return $this->hasMany(Equipment::class);
    }

    public function location()
    {
        return $this->morphOne(Location::class, 'locationable');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'branch_category', 'branch_id', 'category_id');
    }

    public function resellerSales()
    {
        return $this->hasMany(ResellerSale::class, 'branch_id');
    }

    public function resellerSaleItems()
    {
        return $this->hasManyThrough(
            ResellerSaleItem::class,
            ResellerSale::class,
            'branch_id',          // Foreign key on reseller_sales
            'reseller_sale_id',   // Foreign key on reseller_sale_items
            'id',                 // Local key on branches
            'id'                  // Local key on reseller_sales
        );
    }

    public function resellerPaidAmounts()
    {
        return $this->hasManyThrough(
            ResellerSalePaidAmount::class,
            ResellerSale::class,
            'branch_id',
            'reseller_sale_id',
            'id',
            'id'
        );
    }
}
