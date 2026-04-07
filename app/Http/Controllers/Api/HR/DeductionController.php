<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\DeductionResource;
use App\Modules\HR\Payroll\Services\DeductionService;
use Illuminate\Http\Request;

class DeductionController extends Controller
{
    public function __construct(
        protected DeductionService $service
    ) {}

    /**
     * Display a listing of deductions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $filters = $request->only(['active', 'is_penalty', 'is_monthly', 'is_mtd_deduction', 'q']);
        $perPage = $request->query('perPage', 15);

        $deductions = $this->service->getDeductions($filters, $perPage);

        if ($perPage === 'all') {
            return DeductionResource::collection($deductions);
        }

        return DeductionResource::collection($deductions);
    }

    /**
     * Display the specified deduction.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|DeductionResource
     */
    public function show(int $id)
    {
        $deduction = $this->service->getById($id);

        if (!$deduction) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found.',
            ], 404);
        }

        return new DeductionResource($deduction);
    }
}
