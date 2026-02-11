<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreEmployeeApplicationRequest;
use App\Http\Resources\HR\EmployeeApplicationResource;
use App\Models\EmployeeApplicationV2;
use App\Services\HR\Applications\EmployeeApplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeApplicationController extends Controller
{
    /**
     * 🟢 Get all applications (paginated).
     */
    public function index(Request $request)
    {
        $apps = EmployeeApplicationV2::with(['employee', 'leaveRequest', 'advanceRequest'])
            ->when($request->id, fn($q) => $q->where('id', $request->id))
            ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->employee_name, fn($q) => $q->whereHas(
                'employee',
                fn($e) => $e->where('name', 'like', '%' . $request->employee_name . '%')
            ))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->application_type_id, fn($q) => $q->where('application_type_id', $request->application_type_id))
            ->when($request->employee_id, fn($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->from_date, fn($q) => $q->whereDate('application_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('application_date', '<=', $request->to_date))
            ->when($request->search, fn($q) => $q->whereHas(
                'employee',
                fn($e) =>
                $e->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('employee_number', 'like', '%' . $request->search . '%')
            ))
            ->latest()
            ->forBranchManager()
            ->forEmployee()
            ->paginate($request->per_page ?? 20);

        // dd($apps->where('application_type_id',2)->first()->missedCheckinRequest);
        return response()->json([
            'success' => true,
            'message' => 'Applications retrieved successfully',
            'data'    => EmployeeApplicationResource::collection($apps),
            'meta'    => [
                'current_page' => $apps->currentPage(),
                'last_page'    => $apps->lastPage(),
                'total'        => $apps->total(),
            ],
        ]);
    }

    /**
     * 🟢 Show single application.
     */
    public function show($id)
    {
        $app = EmployeeApplicationV2::with(['employee', 'leaveRequest', 'advanceRequest'])->find($id);

        if (! $app) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Application retrieved successfully',
            'data'    => new EmployeeApplicationResource($app),
        ]);
    }

    /**
     * 🟢 Store new application.
     */
    public function store(StoreEmployeeApplicationRequest $request, EmployeeApplicationService $service)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $record = $service->createApplication($data);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Application created successfully',
                'data'    => new EmployeeApplicationResource($record),
            ], 201);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * 🟢 Update application.
     */
    public function update(StoreEmployeeApplicationRequest $request, $id, EmployeeApplicationService $service)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $record = $service->updateApplication($id, $data);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Application updated successfully',
                'data'    => new EmployeeApplicationResource($record),
            ]);
        } catch (Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * 🟢 Delete application.
     */
    public function destroy($id, EmployeeApplicationService $service)
    {
        try {
            $service->deleteApplication($id);

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully',
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * 🟢 Approve application.
     */
    public function approve($id, Request $request, EmployeeApplicationService $service)
    {
        try {
            $record = $service->approveApplication($id, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Application approved successfully',
                'data'    => new EmployeeApplicationResource($record),
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * 🟢 Reject application.
     */
    public function reject($id, Request $request, EmployeeApplicationService $service)
    {
        $reason = $request->input('reason');
        if (! $reason) {
            throw ValidationException::withMessages([
                'reason' => 'Rejection reason is required',
            ]);
        }

        try {
            $record = $service->rejectApplication($id, $request->user()->id, $reason);

            return response()->json([
                'success' => true,
                'message' => 'Application rejected successfully',
                'data'    => new EmployeeApplicationResource($record),
            ]);
        } catch (Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject application',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    public function getTypes()
    {
        // نجيب الكونستانت من الموديل
        $types = EmployeeApplicationV2::APPLICATION_TYPE_NAMES;

        // نحولهم لهيكل مرتب
        $result = collect($types)->map(function ($label, $id) {
            return [
                'id'   => $id,
                'name' => $label,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}
