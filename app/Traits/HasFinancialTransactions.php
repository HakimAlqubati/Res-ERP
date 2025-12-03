<?php

namespace App\Traits;

use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFinancialTransactions
{
    /**
     * العلاقة المورفية لجلب المعاملات المالية الخاصة بهذا الكيان
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(FinancialTransaction::class, 'transactable');
    }

    /**
     * دالة مساعدة لحساب الرصيد الحالي للفرع أو المخزن
     */
    public function getCurrentBalanceAttribute(): float
    {
        // هذه مجرد مسودة منطقية، يمكنك تعديلها حسب قواعد عملك
        // (إيرادات مدفوعة - مصروفات مدفوعة)

        $income = $this->transactions()
            ->income()
            ->paid()
            ->sum('amount');

        $expense = $this->transactions()
            ->expense()
            ->paid()
            ->sum('amount');

        return round($income - $expense, 2);
    }
}
