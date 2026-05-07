<?php

namespace App\Modules\Stock\Reports\GrnConsumption\Filters;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class GrnConsumptionFilter
{
    /**
     * Apply smart filters to the Goods Received Note query.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function apply(Builder $query, array $filters): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        // 1. بحث عام ذكي (Global Search): يبحث في رقم السند والملاحظات واسم المورد معاً
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('grn_number', 'like', $searchTerm)
                  ->orWhere('notes', 'like', $searchTerm)
                  ->orWhereHas('supplier', function ($sq) use ($searchTerm) {
                      $sq->where('name', 'like', $searchTerm);
                  });
            });
        }

        // 2. فلترة برقم السند المباشر
        if (!empty($filters['grn_number'])) {
            $query->where('grn_number', 'like', '%' . $filters['grn_number'] . '%');
        }

        // 3. فلترة النطاق الزمني الذكي (Date Range)
        if (!empty($filters['date_from'])) {
            $query->whereDate('grn_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('grn_date', '<=', $filters['date_to']);
        }

        // 4. فلترة المخزون الراكد أو المتقادم (Aging / Dead Stock) - السندات الأقدم من X يوم
        if (!empty($filters['older_than_days']) && is_numeric($filters['older_than_days'])) {
            $dateThreshold = Carbon::now()->subDays((int) $filters['older_than_days']);
            $query->whereDate('grn_date', '<=', $dateThreshold);
        }

        // 5. الفلترة حسب الموقع أو المورد
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // 6. الفلترة الذكية حسب الارتباط بالفاتورة (يقبل yes/no, 1/0, true/false)
        if (isset($filters['has_invoice']) && $filters['has_invoice'] !== '') {
            $hasInvoice = filter_var($filters['has_invoice'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($hasInvoice === true) {
                $query->whereNotNull('purchase_invoice_id');
            } elseif ($hasInvoice === false) {
                $query->whereNull('purchase_invoice_id');
            }
        }

        // 7. الفلترة بجودة البيانات (وجود مرفقات أو ملاحظات)
        if (isset($filters['has_attachment'])) {
            $hasAttachment = filter_var($filters['has_attachment'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($hasAttachment === true) {
                $query->whereNotNull('attachment')->where('attachment', '!=', '');
            } elseif ($hasAttachment === false) {
                $query->where(function($q) {
                    $q->whereNull('attachment')->orWhere('attachment', '');
                });
            }
        }

        if (isset($filters['has_notes'])) {
            $hasNotes = filter_var($filters['has_notes'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($hasNotes === true) {
                $query->whereNotNull('notes')->where('notes', '!=', '');
            } elseif ($hasNotes === false) {
                $query->where(function($q) {
                    $q->whereNull('notes')->orWhere('notes', '');
                });
            }
        }

        // 8. فلترة الحالة
        if (!empty($filters['status'])) {
            // يقبل مصفوفة من الحالات أو حالة واحدة
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $query->whereIn('status', $statuses);
        }

        // 9. فلترة متقدمة بالمنتجات (Product Inclusion)
        if (!empty($filters['product_id'])) {
            // يقبل منتج واحد أو مصفوفة من المنتجات (مفيد للـ Multi-select)
            $productIds = is_array($filters['product_id']) ? $filters['product_id'] : [$filters['product_id']];
            
            $query->whereHas('inventoryTransactions', function ($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            });
        }

        // 10. الترتيب الذكي (Smart Sorting)
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        
        $allowedSorts = ['id', 'grn_date', 'grn_number', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query;
    }
}
