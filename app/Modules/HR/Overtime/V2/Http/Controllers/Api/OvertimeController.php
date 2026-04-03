<?php

namespace App\Modules\HR\Overtime\V2\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\HR\Overtime\V2\Http\Resources\OvertimeGroupCollection;
use App\Modules\HR\Overtime\V2\Services\OvertimeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeController extends Controller
{
    public function __construct(
        protected OvertimeService $overtimeService
    ) {}

    /**
     * Get list of overtime records grouped by date.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'status' => 'nullable|in:' . implode(',', \App\Models\EmployeeOvertime::STATUSES),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Retrieve grouped overtime data
            $groupedData = $this->overtimeService->getGroupedByDate($request->all());

            // Format using the Resource Collection
            return (new OvertimeGroupCollection($groupedData))
                ->additional([
                    'status' => true,
                    'count'  => $groupedData->flatten()->count(),
                ])
                ->response()
                ->setStatusCode(200);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
