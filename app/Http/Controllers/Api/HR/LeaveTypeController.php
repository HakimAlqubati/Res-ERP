<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    /**
     * GET /hr/leave-types
     * Filters: ?active=1|0&type=weekly|monthly|yearly|special&balance_period=yearly|monthly|other
     * Pagination: ?per_page=15 (default 15). Use per_page=all to disable pagination.
     */
    public function index(Request $request)
    {
        $query = LeaveType::query()
            ->when($request->filled('active'), fn($q) => $q->where('active', (int) $request->boolean('active')))
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('balance_period'), fn($q) => $q->where('balance_period', $request->string('balance_period')))
            ->orderBy('id', 'desc');

        if ($request->string('per_page')->lower() === 'all') {
            return LeaveTypeResource::collection($query->get());
        }

        $perPage = (int) $request->integer('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return LeaveTypeResource::collection($query->paginate($perPage));
    }

    /**
     * GET /hr/leave-types/{leaveType}
     */
    public function show(LeaveType $leaveType)
    {
        return new LeaveTypeResource($leaveType);
    }

    /**
     * GET /hr/leave-types-weekly
     * Returns first active weekly leave whose balance_period=monthly (per your scope).
     */
    public function weekly()
    {
        $leave = LeaveType::weeklyLeave()->first(); // scope returns a builder or you can keep first() here
        if (!$leave) {
            return response()->json([
                'message' => 'Weekly (monthly-balance, active) leave type not found.'
            ], 404);
        }
        return new LeaveTypeResource($leave);
    }

    /**
     * GET /hr/leave-types-monthly-days-sum
     * Sums count_days for (type=weekly & balance_period=monthly); defaults null to 4.
     */
    public function monthlyDaysSum()
    {
        $sum = LeaveType::getMonthlyCountDaysSum(); // uses your scope
        return response()->json(['sum' => (int) $sum]);
    }
}
