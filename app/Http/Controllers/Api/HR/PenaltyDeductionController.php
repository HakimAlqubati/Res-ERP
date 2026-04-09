<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StorePenaltyDeductionRequest;
use App\Modules\HR\Payroll\Services\PenaltyDeductionService;
use Illuminate\Http\Request;

class PenaltyDeductionController extends Controller
{
    public function __construct(
        protected PenaltyDeductionService $penaltyService
    ) {}

    /**
     * Display a listing of penalty deductions.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $penalties = $this->penaltyService->getPenaltiesList(
            $request->only(['employee_id', 'year', 'month', 'status', 'q']),
            $request->integer('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data'    => $penalties->items(),
            'meta'    => [
                'current_page' => $penalties->currentPage(),
                'last_page'    => $penalties->lastPage(),
                'per_page'     => $penalties->perPage(),
                'total'        => $penalties->total(),
            ],
        ]);
    }

    /**
     * Store a newly created penalty deduction.
     *
     * @param \App\Http\Requests\HR\StorePenaltyDeductionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePenaltyDeductionRequest $request)
    {
        $penalty = $this->penaltyService->createPenalty($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Penalty deduction created successfully.',
            'data'    => $penalty,
        ], 201);
    }

    /**
     * Display the specified penalty deduction.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $penalty = $this->penaltyService->getPenaltyById($id);

        if (!$penalty) {
            return response()->json([
                'success' => false,
                'message' => 'Penalty deduction not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $penalty,
        ]);
    }
}
