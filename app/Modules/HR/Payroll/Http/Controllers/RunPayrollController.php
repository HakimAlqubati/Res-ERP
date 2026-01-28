<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Modules\HR\Payroll\DTOs\RunPayrollData;
use App\Http\Controllers\Controller;
use App\Modules\HR\Payroll\Http\Requests\RunPayrollRequest;
use App\Modules\HR\Payroll\Contracts\PayrollRunnerInterface;
use Illuminate\Support\Facades\Log;

class RunPayrollController extends Controller
{

    public function __construct(
        private PayrollRunnerInterface $service
    ) {}
    public function simulate(RunPayrollRequest $request)
    {
        try {
            $dto = $this->makeDto($request);
            $result = $this->service->simulate($dto);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => class_basename($e),
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function run(RunPayrollRequest $request)
    {
        try {
            $dto = $this->makeDto($request);
            $result = $this->service->runAndPersist($dto);
            return response()->json($result, 200); // use 201 if you prefer "Created"
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => class_basename($e),
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    private function makeDto(RunPayrollRequest $request): RunPayrollData
    {
        return RunPayrollData::fromArray($request->validatedPayload());
    }
}
