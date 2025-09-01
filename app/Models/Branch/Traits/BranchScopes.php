<?php

namespace App\Models\Branch\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait BranchScopes
 *
 * يضم جميع Scopes الخاصة بفلترة فروع Branch حسب النوع/الحالة/الصلاحية،
 * بما في ذلك منطق فروع الـ popup (نشِطة/قادمة/منتهية) وسيناريوهات الإظهار المختلفة.
 *
 * ملاحظات:
 * - يفترض أن كلاس Branch يستخدم Trait الثوابت BranchConstants حتى تكون self::TYPE_* متاحة.
 * - جميع الدوال ترجع Builder لتسهيل السَلسَلة (chaining).
 */
trait BranchScopes
{
    /**
     * يقيّد النتائج بناءً على دور المستخدم الحالي:
     * - السوبر أدمن ومدير النظام: بدون تقييد.
     * - مدير الفرع: يظهر فقط فرعه (auth()->user()->branch->id).
     * - الموظف: يظهر فقط الفرع الخاص به (auth()->user()->branch_id).
     */
    public function scopeWithUserCheck(Builder $query): Builder
    {
        $isSuperAdmin    = isSuperAdmin();
        $isSystemManager = isSystemManager();
        $isBranchManager = isBranchManager();
        $isStuff         = isStuff();

        if ($isSuperAdmin || $isSystemManager) {
            return $query;
        }

        if ($isBranchManager) {
            return $query->where('id', auth()->user()->branch->id);
        }

        if ($isStuff) {
            return $query->where('id', auth()->user()->branch_id);
        }

        return $query;
    }

    /**
     * فروع مفعّلة فقط (active = true)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * فروع من نوع "مطبخ مركزي"
     */
    public function scopeCentralKitchens(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CENTRAL_KITCHEN);
    }

    /**
     * فروع من نوع "موزّع" (Reseller)
     */
    public function scopeResellers(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESELLER);
    }

    /**
     * فروع من نوع "Branch" فقط
     */
    public function scopeBranches(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    /**
     * فروع من نوع "HQ" فقط
     */
    public function scopeHQBranches(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_HQ);
    }

    /**
     * فروع عادية (Branch + HQ)
     */
    public function scopeNormal(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_BRANCH, self::TYPE_HQ]);
    }

    /**
     * جميع الأنواع المعرفة في BranchConstants::TYPES
     */
    public function scopeWithAllTypes(Builder $query): Builder
    {
        return $query->whereIn('type', self::TYPES);
    }

    /**
     * فروع من نوع "Popup" فقط (بدون النظر للحالة)
     */
    public function scopePopups(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_POPUP);
    }

    /**
     * منطق قديم/خاص: يعيد كل الفروع ما عدا popup المنتهية.
     * - أي نوع غير popup يظهر دائمًا.
     * - popup تظهر فقط إذا end_date >= اليوم.
     */
    public function scopeActivePopups(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function (Builder $q) use ($today) {
            $q->where('type', '!=', self::TYPE_POPUP)
                ->orWhere(function (Builder $q2) use ($today) {
                    $q2->where('type', self::TYPE_POPUP)
                        ->where('end_date', '>=', $today);
                });
        });
    }

    /**
     * Popup نشِطة فقط:
     * - type = popup
     * - start_date NULL أو <= اليوم
     * - end_date   NULL أو >= اليوم
     */
    public function scopePopupActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('type', self::TYPE_POPUP)
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $today);
            })
            ->where(function (Builder $q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            });
    }

    /**
     * Popup قادمة فقط:
     * - type = popup
     * - start_date > اليوم
     */
    public function scopePopupUpcoming(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('type', self::TYPE_POPUP)
            ->whereDate('start_date', '>', $today);
    }

    /**
     * Popup منتهية فقط:
     * - type = popup
     * - end_date < اليوم (ومعرّفة وليست NULL)
     */
    public function scopePopupExpired(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where('type', self::TYPE_POPUP)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today);
    }

    public function scopePopupActiveAndExpired(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_POPUP);
    }

    public function scopeWithPopupsActiveAndExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('type', '!=', self::TYPE_POPUP) // كل الفروع العادية
                ->orWhere('type', self::TYPE_POPUP);    // وكل الـ popup (active + expired)
        });
    }

    /**
     * يفلتر فروع الـ popup حسب الحالة المطلوبة.
     *
     * @param Builder $query
     * @param string  $status one of: 'active' | 'expired' | 'upcoming' | 'all'
     */
    public function scopePopupStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'active'   => $this->scopePopupActive($query),
            'expired'  => $this->scopePopupExpired($query),
            'upcoming' => $this->scopePopupUpcoming($query),
            default    => $this->scopePopups($query), // 'all'
        };
    }

    /**
     * يستثني popup المنتهية ويُظهر باقي الأنواع + popup النشِطة/المسموح بعرضها:
     * - الأنواع غير popup: تظهر دائمًا.
     * - popup:
     *   - end_date NULL أو >= اليوم
     *   - start_date NULL أو <= اليوم
     */
    public function scopeExcludeExpiredPopups(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->where(function (Builder $q) use ($today) {
            $q->where('type', '!=', self::TYPE_POPUP)
                ->orWhere(function (Builder $q2) use ($today) {
                    $q2->where('type', self::TYPE_POPUP)
                        ->where(function (Builder $q3) use ($today) {
                            $q3->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $today);
                        })
                        ->where(function (Builder $q3) use ($today) {
                            $q3->whereNull('start_date')
                                ->orWhereDate('start_date', '<=', $today);
                        });
                });
        });
    }

    /**
     * يُظهر الفروع غير المخفية فقط (is_hidden = false)
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    /**
     * مرادف لفلترة "Reseller" (قديم/توافقية)
     */
    public function scopeReseller(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESELLER);
    }

    /**
     * يستبعد نوع "Reseller"
     */
    public function scopeNotReseller(Builder $query): Builder
    {
        return $query->where('type', '!=', self::TYPE_RESELLER);
    }
}
