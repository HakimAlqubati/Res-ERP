<?php

namespace App\Services\Reports\ResellerBranches;

use App\Models\Branch;
use Illuminate\Support\Collection;

class BranchSalesBalanceReportService
{
    /**
     * تقرير كامل يحتوي على المبيعات والمدفوعات والرصيد
     */
    public function generate(): Collection
    {
        return Branch::where('type', Branch::TYPE_RESELLER)

            ->get()
            ->map(function ($branch) {
                $sales = $branch->total_sales;
                $paid = $branch->total_paid;
                $balance = $sales - $paid;

                return [
                    'branch' => $branch->name,
                    'sales' => $sales,
                    'payments' => $paid,
                    'balance' => $balance,
                ];
            });
    }
}