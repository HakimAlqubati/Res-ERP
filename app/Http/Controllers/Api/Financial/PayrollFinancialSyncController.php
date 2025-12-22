<?php

namespace App\Http\Controllers\Api\Financial;

use App\Http\Controllers\Controller;
use App\Services\Financial\PayrollFinancialSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Payroll Financial Sync operations.
 * 
 * Provides endpoints to sync payroll data with the financial system.
 */
class PayrollFinancialSyncController extends Controller
{
    public function __construct(
        protected PayrollFinancialSyncService $syncService
    ) {}

    /**
     * Sync a specific payroll run to financial transactions.
     * 
     * POST /api/financial/payroll/sync/{payrollRunId}
     */
    public function syncPayrollRun(int $payrollRunId): JsonResponse
    {
        $result = $this->syncService->syncPayrollRun($payrollRunId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Sync payroll runs for a specific branch.
     * 
     * POST /api/financial/payroll/sync/branch/{branchId}
     * 
     * Query params: month, year
     */
    public function syncBranch(Request $request, int $branchId): JsonResponse
    {
        $options = $request->only(['month', 'year']);

        $result = $this->syncService->syncPayrollsForBranch($branchId, $options);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Sync all payroll runs for all branches.
     * 
     * POST /api/financial/payroll/sync/all
     * 
     * Query params: month, year
     */
    public function syncAll(Request $request): JsonResponse
    {
        $options = $request->only(['month', 'year']);

        $result = $this->syncService->syncAllBranches($options);

        return response()->json($result);
    }

    /**
     * Get sync status for a payroll run.
     * 
     * GET /api/financial/payroll/status/{payrollRunId}
     */
    public function getSyncStatus(int $payrollRunId): JsonResponse
    {
        $result = $this->syncService->getSyncStatus($payrollRunId);

        return response()->json($result);
    }

    /**
     * Delete financial transactions for a payroll run (for re-sync).
     * 
     * DELETE /api/financial/payroll/sync/{payrollRunId}
     */
    public function deleteSync(int $payrollRunId): JsonResponse
    {
        $result = $this->syncService->deletePayrollRunTransactions($payrollRunId);

        return response()->json($result);
    }

    /**
     * Re-sync a payroll run (delete existing and create new).
     * 
     * PUT /api/financial/payroll/sync/{payrollRunId}
     */
    public function resync(int $payrollRunId): JsonResponse
    {
        // First delete existing transactions
        $this->syncService->deletePayrollRunTransactions($payrollRunId);

        // Then sync again
        $result = $this->syncService->syncPayrollRun($payrollRunId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
