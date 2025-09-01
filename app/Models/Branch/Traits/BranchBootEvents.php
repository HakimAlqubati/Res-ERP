<?php

namespace App\Models\Branch\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Store;

trait BranchBootEvents
{
    protected static function booted()
    {
        // 🔒 Global scopes على حسب دور المستخدم
        if (auth()->check()) {
            if (isBranchManager()) {
                // مساحة لتقييد إضافي إن رغبت
                static::addGlobalScope('branch_manager_scope', function ($builder) {
                    // مثال: $builder->where('id', auth()->user()->branch_id);
                });
            } elseif (isStuff()) {
                static::addGlobalScope('stuff_scope', function ($builder) {
                    $builder->where('id', auth()->user()->branch_id);
                });
            }
        }

        // 🏪 إنشاء Store تلقائي عند إنشاء الفرع (إن لم يكن موجود)
        static::created(function ($branch) {
            if ($branch->store_id) {
                return;
            }

            DB::transaction(function () use ($branch) {
                $store = Store::create([
                    'name'   => $branch->name . ' Store',
                    'active' => true,
                ]);

                $branch->update(['store_id' => $store->id]);
            });
        });
    }
}
