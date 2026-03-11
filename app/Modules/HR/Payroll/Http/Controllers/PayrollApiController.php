<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Payroll\Services\PayrollService;
use App\Http\Resources\PayrollResource;
use Illuminate\Http\Request;

class PayrollApiController extends Controller
{
    public function __construct(
        private PayrollService $payrollService
    ) {}

    /**
     * Get a paginated list of payrolls with optional filtering.
     */
    public function index(Request $request)
    {
        $payrolls = $this->payrollService->getPayrolls(
            $request->all(),
            $request->get('per_page', 15)
        );

        return PayrollResource::collection($payrolls);
    }

    /**
     * Get details of a specific payroll record.
     */
    public function show($id)
    {
        $payroll = $this->payrollService->getPayrollById($id);

        return new PayrollResource($payroll);
    }
}
