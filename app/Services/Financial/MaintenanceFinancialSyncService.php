<?php

namespace App\Services\Financial;

use App\Enums\FinancialCategoryCode;
use App\Models\MaintenanceCost;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * خدمة مزامنة تكاليف الصيانة مع النظام المالي
 * 
 * تقوم بإنشاء معاملات مالية من سجلات تكاليف الصيانة
 */
class MaintenanceFinancialSyncService
{
    /**
     * مزامنة تكلفة صيانة واحدة مع النظام المالي
     *
     * @param int $maintenanceCostId
     * @return array
     */
    public function syncMaintenanceCost(int $maintenanceCostId): array
    {
        $cost = MaintenanceCost::with(['costable', 'branch'])->find($maintenanceCostId);

        if (!$cost) {
            return [
                'success' => false,
                'message' => "MaintenanceCost with ID {$maintenanceCostId} not found.",
            ];
        }

        // تحقق مما إذا كانت مزامنة بالفعل
        if ($cost->synced_to_financial) {
            return [
                'success' => true,
                'status' => 'skipped',
                'message' => 'Cost already synced to financial system.',
            ];
        }

        // تحقق من وجود مبلغ
        if ($cost->amount <= 0) {
            return [
                'success' => false,
                'message' => 'Cost amount must be greater than 0.',
            ];
        }

        // تحديد الفئة المالية بناءً على نوع التكلفة
        $categoryCode = $cost->isPurchase()
            ? FinancialCategoryCode::EQUIPMENT_PURCHASE
            : FinancialCategoryCode::MAINTENANCE_REPAIR;

        $category = FinancialCategory::findByCode($categoryCode);

        if (!$category) {
            return [
                'success' => false,
                'message' => "Financial category '{$categoryCode}' not found. Please run PayrollHRFinancialCategorySeeder.",
            ];
        }

        try {
            DB::transaction(function () use ($cost, $category) {
                // بناء الوصف
                $description = $this->buildDescription($cost);

                // إنشاء المعاملة المالية
                FinancialTransaction::create([
                    'branch_id' => $cost->branch_id,
                    'category_id' => $category->id,
                    'amount' => $cost->amount,
                    'type' => FinancialTransaction::TYPE_EXPENSE,
                    'transaction_date' => $cost->cost_date ?? now(),
                    'status' => FinancialTransaction::STATUS_PAID,
                    'description' => $description,
                    'reference_type' => MaintenanceCost::class,
                    'reference_id' => $cost->id,
                    'created_by' => auth()->id() ?? $cost->created_by ?? 1,
                    'month' => $cost->cost_date ? $cost->cost_date->month : now()->month,
                    'year' => $cost->cost_date ? $cost->cost_date->year : now()->year,
                ]);

                // تحديث علامة المزامنة
                $cost->markAsSynced();
            });

            Log::info('MaintenanceCost synced to financial system', [
                'maintenance_cost_id' => $cost->id,
                'amount' => $cost->amount,
                'cost_type' => $cost->cost_type,
            ]);

            return [
                'success' => true,
                'status' => 'synced',
                'message' => 'Maintenance cost synced to financial system successfully.',
                'maintenance_cost_id' => $maintenanceCostId,
                'amount' => $cost->amount,
            ];
        } catch (\Exception $e) {
            Log::error('MaintenanceFinancialSync Error', [
                'maintenance_cost_id' => $maintenanceCostId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * بناء وصف المعاملة المالية
     */
    private function buildDescription(MaintenanceCost $cost): string
    {
        $typeLabel = $cost->cost_type_label;
        $costableType = class_basename($cost->costable_type);
        $costableId = $cost->costable_id;

        $description = "{$typeLabel}";

        if ($cost->costable) {
            $name = $cost->costable->name ?? $cost->costable->description ?? "#{$costableId}";
            $description .= " - {$costableType}: {$name}";
        }

        if ($cost->description) {
            $description .= " ({$cost->description})";
        }

        return $description;
    }

    /**
     * مزامنة جميع التكاليف غير المزامنة
     *
     * @return array
     */
    public function syncAllUnsynced(): array
    {
        $costs = MaintenanceCost::notSynced()
            ->where('amount', '>', 0)
            ->get();

        $synced = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($costs as $cost) {
            $result = $this->syncMaintenanceCost($cost->id);

            if ($result['success'] && ($result['status'] ?? '') === 'synced') {
                $synced++;
            } else {
                $errors++;
                $errorDetails[] = [
                    'maintenance_cost_id' => $cost->id,
                    'error' => $result['message'],
                ];
            }
        }

        return [
            'success' => true,
            'message' => 'Sync completed.',
            'total_costs' => $costs->count(),
            'synced' => $synced,
            'errors' => $errors,
            'error_details' => $errorDetails,
        ];
    }

    /**
     * حذف المعاملة المالية المرتبطة بتكلفة صيانة
     *
     * @param int $maintenanceCostId
     * @return array
     */
    public function deleteFinancialTransaction(int $maintenanceCostId): array
    {
        $deleted = FinancialTransaction::where('reference_type', MaintenanceCost::class)
            ->where('reference_id', $maintenanceCostId)
            ->delete();

        // تحديث علامة المزامنة
        MaintenanceCost::where('id', $maintenanceCostId)
            ->update(['synced_to_financial' => false]);

        return [
            'success' => true,
            'message' => "Deleted {$deleted} financial transaction(s).",
            'deleted' => $deleted,
        ];
    }
}
