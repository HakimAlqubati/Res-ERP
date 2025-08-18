<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalaryTransaction;
use App\Services\HR\SalaryHelpers\SalarySlipService;
use Carbon\Carbon;

class SalarySlipController extends Controller
{
    public function __construct(
        protected SalarySlipService $slipService
    ) {}

    public function show(int $employee, int $year, int $month)
    {
        $payload = $this->slipService->build($employee, $year, $month);

        return view('export.reports.hr.salaries.salary-slip', $payload);
    }
}
