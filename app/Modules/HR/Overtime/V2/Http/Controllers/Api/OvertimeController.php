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
            // Retrieve grouped overtime data
            $groupedData = $this->overtimeService->getGroupedByDate($request->all());

            // Format using the Resource Collection
            return response()->json([
                'status' => true,
                'data' => new OvertimeGroupCollection($groupedData),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
