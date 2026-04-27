<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StorePenaltyDeductionRequest;
use App\Http\Resources\HR\PenaltyDeductionResource;
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

        return PenaltyDeductionResource::collection($penalties);
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

        return (new PenaltyDeductionResource($penalty))
            ->additional([
                'success' => true,
                'message' => 'Penalty deduction created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
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
                'message' => __('Penalty deduction not found.'),
            ], 404);
        }

        return new PenaltyDeductionResource($penalty);
    }

    /**
     * Approve the specified penalty deduction.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        try {
            $penalty = $this->penaltyService->approvePenalty($id, auth()->id());

            if (!$penalty) {
                return response()->json([
                    'success' => false,
                    'message' => __('Penalty deduction not found.'),
                ], 404);
            }

            return (new PenaltyDeductionResource($penalty))
                ->additional([
                    'success' => true,
                    'message' => __('Penalty deduction approved successfully.'),
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject the specified penalty deduction.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        try {
            $penalty = $this->penaltyService->rejectPenalty($id, auth()->id(), $request->input('rejected_reason'));

            if (!$penalty) {
                return response()->json([
                    'success' => false,
                    'message' => __('Penalty deduction not found.'),
                ], 404);
            }

            return (new PenaltyDeductionResource($penalty))
                ->additional([
                    'success' => true,
                    'message' => __('Penalty deduction rejected successfully.'),
                ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
