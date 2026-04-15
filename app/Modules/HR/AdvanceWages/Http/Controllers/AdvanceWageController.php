<?php

namespace App\Modules\HR\AdvanceWages\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdvanceWage;
use App\Modules\HR\AdvanceWages\Http\Requests\StoreAdvanceWageRequest;
use App\Modules\HR\AdvanceWages\Http\Requests\UpdateAdvanceWageRequest;
use App\Modules\HR\AdvanceWages\Http\Resources\AdvanceWageResource;
use App\Modules\HR\AdvanceWages\Interfaces\AdvanceWageServiceInterface;
use Illuminate\Http\Request;

class AdvanceWageController extends Controller
{
    public function __construct(protected AdvanceWageServiceInterface $advanceWageService)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min($perPage, 100);
        
        $advanceWages = $this->advanceWageService->getAll($request->all(), $perPage);
        
        return AdvanceWageResource::collection($advanceWages);
    }

    public function show(int $id)
    {
        $advanceWage = $this->advanceWageService->findById($id);
        
        return new AdvanceWageResource($advanceWage);
    }

    public function store(StoreAdvanceWageRequest $request)
    {
        try {
            $advanceWage = $this->advanceWageService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => __('Advance wage created successfully.'),
                'data'    => new AdvanceWageResource($advanceWage->load(['employee', 'creator']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function update(UpdateAdvanceWageRequest $request, AdvanceWage $advanceWage)
    {
        try {
            $updatedWage = $this->advanceWageService->update($advanceWage, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => __('Advance wage updated successfully.'),
                'data'    => new AdvanceWageResource($updatedWage->load(['employee', 'creator']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function destroy(AdvanceWage $advanceWage)
    {
        try {
            $this->advanceWageService->delete($advanceWage);
            
            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function approve(AdvanceWage $advanceWage)
    {
        try {
            $approvedWage = $this->advanceWageService->approve($advanceWage);
            
            return response()->json([
                'success' => true,
                'message' => __('Advance wage approved successfully.'),
                'data'    => new AdvanceWageResource($approvedWage->load(['employee', 'creator', 'approver']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function cancel(AdvanceWage $advanceWage)
    {
        try {
            $cancelledWage = $this->advanceWageService->cancel($advanceWage);
            
            return response()->json([
                'success' => true,
                'message' => __('Advance wage cancelled successfully.'),
                'data'    => new AdvanceWageResource($cancelledWage->load(['employee', 'creator']))
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
