<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;

class Store extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;
    protected $fillable = [
        'name',
        'location',
        'active',
        'default_store',
        'storekeeper_id',
        'is_central_kitchen',
    ];
    protected $auditInclude = [
        'name',
        'location',
        'active',
        'default_store',
        'storekeeper_id',
        'is_central_kitchen',
    ];

    protected $appends = ['storekeeper_name', 'storekeeper_email'];
    /**
     * Scope to get only active stores.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get the default store.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDefaultStore($query)
    {
        return $query->where('default_store', true)->active()->first();
    }
    public function scopeCentralKitchen($query)
    {
        return $query->where('is_central_kitchen', true)->first();
    }

    public function branch()
    {
        return $this->hasOne(Branch::class, 'store_id');
    }

    public function storekeeper()
    {
        return $this->belongsTo(User::class, 'storekeeper_id');
    }

    /**
     * أمناء المخزن الإضافيون المرتبطون بالمخزن عبر جدول store_user
     */
    public function storekeepers()
    {
        return $this->belongsToMany(User::class, 'store_user')->withTimestamps();
    }

    /**
     * كل أمناء المخزن (الأساسي + الإضافيون) كـ query builder
     */
    public function allStorekeepers()
    {
        return User::whereIn('id', $this->all_storekeeper_ids);
    }

    /**
     * مصفوفة بكل IDs أمناء المخزن (الأساسي + الإضافيون)
     */
    public function getAllStorekeeperIdsAttribute(): array
    {
        $extraIds = $this->storekeepers()->pluck('users.id')->toArray();
        return array_values(array_unique(array_merge(
            array_filter([$this->storekeeper_id]),
            $extraIds
        )));
    }

    public function getStorekeeperNameAttribute()
    {
        $names = [];
        if ($this->storekeeper) {
            $names[] = $this->storekeeper->name;
        }

        if ($this->relationLoaded('storekeepers')) {
            $extra = $this->storekeepers->pluck('name')->toArray();
        } else {
            $extra = $this->storekeepers()->pluck('name')->toArray();
        }

        $all = array_values(array_unique(array_merge($names, $extra)));

        return !empty($all) ? implode(', ', $all) : '';
    }

    public function getStorekeeperEmailAttribute(): ?string
    {
        $emails = [];
        if ($this->storekeeper && $this->storekeeper->email) {
            $emails[] = $this->storekeeper->email;
        }

        if ($this->relationLoaded('storekeepers')) {
            $extra = $this->storekeepers->pluck('email')->filter()->toArray();
        } else {
            $extra = $this->storekeepers()->pluck('email')->filter()->toArray();
        }

        $all = array_values(array_unique(array_merge($emails, $extra)));

        return !empty($all) ? implode(', ', $all) : null;
    }

    public function scopeCentralKitchenStores($query)
    {
        if (auth()->user()?->branch?->is_kitchen) {
            return $query->where('id', auth()->user()->branch->store_id);
        };
    }

    /**
     * المخازن المسموح للمستخدم برؤيتها (مصدر واحد للحقيقة يُستخدم في المخازن والجرودات وغيرها).
     *
     * القواعد:
     * 1) استثناء المخزن الرئيسي (default_store) دائماً.
     * 2) مدير الفرع: يرى فقط المخزن المرتبط بفرعه.
     * 3) المستخدم المرتبط بفروع إضافية من نوع تصنيعي: يرى مخازن تلك الفروع.
     * 4) أمين المخزن الرئيسي: لا يُعرض له شيء.
     * - السوبر أدمن ومدير النظام: كل المخازن (عدا الرئيسي).
     * - أي مستخدم آخر: لا شيء.
     *
     * Usage:
     *   Store::visibleTo()->pluck('name', 'id');
     *   StockInventory::whereIn('store_id', Store::visibleTo()->select('id'));
     */
    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        $user ??= auth()->user();

        // (1) استثناء المخزن الرئيسي دائماً
        $query->where(function (Builder $q) {
            $q->where('stores.default_store', false)->orWhereNull('stores.default_store');
        });

        // بدون مستخدم أو أمين المخزن الرئيسي (4) => لا شيء
        if (!$user || $user->isDefaultStoreManager()) {
            return $query->whereRaw('1 = 0');
        }

        // السوبر أدمن ومدير النظام: كل المخازن (عدا الرئيسي)
        if ($user->isSuperAdmin() || $user->isSystemManager()) {
            return $query;
        }

        $storeIds = [];

        // (2) مدير الفرع: مخزن فرعه فقط
        if ($user->isBranchManager()) {
            $storeIds = array_merge(
                $storeIds,
                Branch::withoutGlobalScopes()
                    ->forBranchManager('id')
                    ->whereNotNull('store_id')
                    ->pluck('store_id')
                    ->all()
            );
        }

        // (3) الفروع الإضافية من النوع التصنيعي => مخازنها
        $storeIds = array_merge(
            $storeIds,
            $user->branches()
                ->withoutGlobalScopes()
                ->centralKitchens()
                ->whereNotNull('branches.store_id')
                ->pluck('branches.store_id')
                ->all()
        );

        return $query->whereIn('stores.id', array_values(array_unique($storeIds)));
    }

    public function scopeWithManagedStores($query)
    {
        if (isFinanceManager()) {
            return $query;
        }
        if (isStoreManager()) {
            return $query->whereIn('id', auth()->user()->managed_stores_ids);
        } else {
            return $query;
        }
    }


    public static function boot()
    {
        parent::boot();

        static::updating(function ($store) {
            if ($store->default_store) {
                // Unset the previous default store
                Store::where('default_store', true)
                    ->where('id', '!=', $store->id) // Exclude the current store
                    ->update(['default_store' => false]);
            }
            // if ($store->is_central_kitchen) {
            //     // Unset the previous default store
            //     Store::where('is_central_kitchen', true)
            //         ->where('id', '!=', $store->id) // Exclude the current store
            //         ->update(['is_central_kitchen' => false]);
            // }
        });

        // static::saving(function ($store) {
        //     if ($store->default_store) {
        //         // Check if there is already a default store
        //         $existingDefaultStore = Store::where('default_store', true)
        //             ->where('id', '!=', $store->id) // Exclude the current store being updated
        //             ->exists();

        //         if ($existingDefaultStore) {
        //             throw new \Exception('Only one default store is allowed.');
        //         }
        //     }
        // });
    }

    public function branches()
    {
        return $this->hasMany(Branch::class, 'store_id');
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_store');
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(\App\Models\PurchaseInvoice::class);
    }

    /**
     * يعرِض فقط المخازن التي لديها مشتريات (Purchase Invoices) غير محذوفة.
     */
    public function scopeHasPurchases($query)
    {
        return $query->whereExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('purchase_invoices as pi')
                ->whereColumn('pi.store_id', 'stores.id')
                ->whereNull('pi.deleted_at'); // تجاهُل المحذوفة سوفت
        });
    }
}
