<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Http\Requests\HR\Employee\StoreTerminationRequest;
use App\Http\Requests\HR\Employee\RejectTerminationRequest;
use App\Http\Resources\HR\Employee\EmployeeServiceTerminationResource;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use Illuminate\Http\Request;

class EmployeeServiceTerminationController extends Controller
{
    public function __construct(protected EmployeeLifecycleService $lifecycleService)
    {
    }

    /**
     * Get a list of all terminations.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $query = EmployeeServiceTermination::query()->with(['employee', 'createdBy', 'approvedBy', 'rejectedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $terminations = $query->latest()->paginate($perPage);

        return EmployeeServiceTerminationResource::collection($terminations);
    }

    /**
     * Show a single termination request.
     */
    public function show(int $id)
    {
        $termination = EmployeeServiceTermination::with(['employee', 'createdBy', 'approvedBy', 'rejectedBy'])->findOrFail($id);

        return new EmployeeServiceTerminationResource($termination);
    }

    /**
     * Request a termination for an employee.
     */
    public function store(StoreTerminationRequest $request, Employee $employee)
    {
        try {
            $termination = $this->lifecycleService->requestTermination($employee, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('Termination request created successfully.'),
                'data'    => new EmployeeServiceTerminationResource($termination->load(['employee', 'createdBy']))
            ], 201);
        } catch (\Exception $e) {
            // Let the global exception handler deal with ValidationException, only catch general errors
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Approve a termination request.
     */
    public function approve(EmployeeServiceTermination $termination)
    {
        try {
            if ($termination->status !== EmployeeServiceTermination::STATUS_PENDING) {
                return response()->json(['success' => false, 'message' => __('Termination request is not pending.')], 400);
            }

            $this->lifecycleService->approveTermination($termination);

            return response()->json([
                'success' => true,
                'message' => __('Termination request approved successfully.'),
                'data'    => new EmployeeServiceTerminationResource($termination->refresh()->load(['employee', 'approvedBy']))
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject a termination request.
     */
    public function reject(RejectTerminationRequest $request, EmployeeServiceTermination $termination)
    {
        try {
            if ($termination->status !== EmployeeServiceTermination::STATUS_PENDING) {
                return response()->json(['success' => false, 'message' => __('Termination request is not pending.')], 400);
            }

            $this->lifecycleService->rejectTermination($termination, $request->validated());

            return response()->json([
                'success' => true,
                'message' => __('Termination request rejected successfully.'),
                'data'    => new EmployeeServiceTerminationResource($termination->refresh()->load(['employee', 'rejectedBy']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
