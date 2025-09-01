<?php

namespace App\Models\Branch\Traits;

use Carbon\Carbon;

trait BranchAttributes
{
    // ✅ حقل مشتق: هل هو مطبخ مركزي؟
    public function getIsKitchenAttribute(): bool
    {
        return $this->type === self::TYPE_CENTRAL_KITCHEN;
    }

    public function getIsBranchAttribute(): bool
    {
        return $this->type === self::TYPE_BRANCH;
    }

    public function getIsPopupAttribute(): bool
    {
        return $this->type === self::TYPE_POPUP;
    }

    public function getTypeTitleAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BRANCH          => __('lang.branch'),
            self::TYPE_CENTRAL_KITCHEN => __('lang.central_kitchen'),
            self::TYPE_HQ              => __('lang.hq'),
            self::TYPE_POPUP           => __('lang.popup_branch'),
            self::TYPE_RESELLER        => __('lang.reseller'),
            default                    => __('lang.unknown'),
        };
    }

    // تسمية قديمة متوافقة (إن كانت مستخدمة في واجهات قديمة)
    public function getisCentralKitchenAttribute(): bool
    {
        return $this->getIsKitchenAttribute();
    }

    public function getValidStoreIdAttribute(): ?int
    {
        if ($this->is_kitchen && $this->categories()->exists() && $this->store) {
            return $this->store_id;
        }

        if (
            auth()->check()
            && $this->manager_id === auth()->id()
            && $this->is_kitchen
            && $this->store
        ) {
            return $this->store_id;
        }

        return null;
    }

    public function getCustomizedCategoriesAttribute(): array
    {
        return $this->categories->map(fn($c) => [
            'id'   => $c->id,
            'name' => $c->name,
        ])->all();
    }

    public function getCategoryNamesAttribute(): array
    {
        return $this->categories->pluck('name')->all();
    }

    public function hasStore(): bool
    {
        return ! is_null($this->store?->id);
    }

    public function getIsPopupActiveAttribute(): bool
    {
        $today = now()->toDateString();

        $startsOk = is_null($this->start_date) || $this->start_date->toDateString() <= $today;
        $endsOk   = is_null($this->end_date)   || $this->end_date->toDateString()   >= $today;

        return $this->type === self::TYPE_POPUP && $startsOk && $endsOk;
    }

    /** 'upcoming' | 'active' | 'expired' | 'none' */
    public function getPopupStatusAttribute(): string
    {
        if ($this->type !== self::TYPE_POPUP) {
            return 'none';
        }

        // Upcoming: start_date بعد اليوم
        if (! empty($this->start_date)) {
            $start = $this->start_date instanceof \Illuminate\Support\Carbon
                ? $this->start_date
                : \Illuminate\Support\Carbon::parse($this->start_date);

            if ($start->startOfDay()->gt(now())) {
                return 'upcoming';
            }
        }

        // Expired: اعتمادًا على المنطق الموحّد أعلاه
        if ($this->is_expired) {
            return 'expired';
        }

        return 'active';
    }


    public function getStatusLabelAttribute(): string
    {
        // لو النوع Popup وتاريخ الانتهاء أصغر من اليوم = منتهي
        if ($this->type === self::TYPE_POPUP) {
            $today = now()->toDateString();

            if (! is_null($this->end_date) && $this->end_date->toDateString() < $today) {
                return 'Expired';
            }

            if (! is_null($this->start_date) && $this->start_date->toDateString() > $today) {
                return 'Upcoming';
            }

            return 'Active';
        }

        // باقي الأنواع
        return 'Normal';
    }

    public function getIsExpiredAttribute(): bool
    {
        // الفروع غير الـ popup لا تُعتبر منتهية
        if ($this->type !== self::TYPE_POPUP) {
            return false;
        }

        // لو ما في end_date فالمبدأ أنه غير محدود المدة => ليس منتهي
        if (empty($this->end_date)) {
            return false;
        }

        // نحلّل التاريخ بأمان حتى لو كان String (بدون اعتماد على $casts)
        $end = $this->end_date instanceof Carbon
            ? $this->end_date->copy()
            : Carbon::parse($this->end_date);

        // يُعتبر نشِط حتى نهاية يوم الانتهاء، وبعدها فقط يصبح منتهي
        return $end->endOfDay()->lt(now());
    }
    public function getNameAttribute($value): string
    {
        $name = (string) $value;

        if ($this->type === self::TYPE_POPUP) {
            if ($this->is_expired) {
                return $name . ' ' . __('lang.expired');
            }

            if ($this->popup_status === 'upcoming') {
                return $name . ' ' . __('lang.upcoming');
            }
        }

        return $name;
    }
}
