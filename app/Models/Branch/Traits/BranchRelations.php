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
use App\Models\WorkPeriod;
use App\Models\Employee;
use App\Models\LeaveType;

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

    public function manufacturingCategories()
    {
        return $this->belongsToMany(Category::class, 'branch_category', 'branch_id', 'category_id')
                    ->where('is_manafacturing', true);
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

    public function workPeriods()
    {
        return $this->hasMany(WorkPeriod::class, 'branch_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'branch_id');
    }

    public function leaveTypes()
    {
        return $this->belongsToMany(LeaveType::class, 'hr_branch_leave_types', 'branch_id', 'leave_type_id');
    }

    /**
     * المستخدمين المُعيّنين لهذا الفرع كفرع إضافي (عبر branch_user)
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'branch_user')
                    ->withTimestamps();
    }

    /**
     * المستخدمين المرتبطين بهذا الفرع بشكل مباشر (branch_id)
     */
    public function directUsers()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    /**
     * كل المستخدمين المرتبطين بهذا الفرع (بشكل مباشر أو كفرع إضافي)
     */
    public function allUsers()
    {
        return User::query()->where(function ($query) {
            $query->where('branch_id', $this->id)
                  ->orWhereHas('branches', fn($q) => $q->where('branches.id', $this->id));
        });
    }

    /**
     * مساعدين الطباخ الرئيسي / مدير المعمل المركزي
     */
    public function chefAssistants()
    {
        return $this->belongsToMany(User::class, 'chef_assistants')
                    ->withTimestamps();
    }
}
