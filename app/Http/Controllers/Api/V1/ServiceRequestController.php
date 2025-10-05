<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\Maintenance\StoreServiceRequestRequest;
use App\Http\Requests\HR\Maintenance\UpdateServiceRequestRequest;
use App\Http\Resources\HR\Maintenance\ServiceRequestResource;
use App\Http\Resources\HR\Maintenance\ServiceRequestCommentResource;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestComment;
use App\Models\ServiceRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    public function index(Request $req)
    {
        $q = ServiceRequest::query()
            ->with(['branch', 'branchArea', 'assignedTo', 'equipment'])
            ->when($req->input('filter.search'), function ($x, $v) {
                $x->where(function ($q) use ($v) {
                    $q->where('description', 'like', "%{$v}%")
                        ->orWhere('id', $v);
                });
            })
            ->when($req->input('filter.status'), fn($x, $v) => $x->where('status', $v))
            ->when($req->input('filter.urgency'), fn($x, $v) => $x->where('urgency', $v))
            ->when($req->input('filter.impact'),  fn($x, $v) => $x->where('impact',  $v))
            ->when($req->input('filter.branch_id'), fn($x, $v) => $x->where('branch_id', $v))
            ->when($req->input('filter.assigned_to'), fn($x, $v) => $x->where('assigned_to', $v))
            ->when($req->input('filter.equipment_id'), fn($x, $v) => $x->where('equipment_id', $v));

        // sort
        $sort = $req->input('sort', '-created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['created_at', 'status', 'urgency', 'impact', 'branch_id'];
        if (! in_array($col, $allowed)) $col = 'created_at';
        $q->orderBy($col, $dir);

        $per = min((int) $req->input('per_page', 15), 100);
        return ServiceRequestResource::collection($q->paginate($per));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        return new ServiceRequestResource(
            $serviceRequest->load(['branch', 'branchArea', 'assignedTo', 'equipment'])
        );
    }

    public function store(StoreServiceRequestRequest $req)
    {


        // dd('sdf');
        try {
            $data = $req->validated();
            $data['created_by'] = auth()->id();

            $sr = DB::transaction(function () use ($data) {
                $sr = ServiceRequest::create($data);
                $sr->logs()->create([
                    'action'      => 'created',
                    'description' => 'Service request created',
                    'user_id'     => auth()->id(),
                    'created_by'     => auth()->id(),
                ]);
                // إلى سجل الجهاز (لو مرتبط)
                $sr->logToEquipment(\App\Models\EquipmentLog::ACTION_SERVICED, 'Service request created');
                return $sr->load(['branch', 'branchArea', 'assignedTo', 'equipment']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Service request created successfully',
                'data'    => new ServiceRequestResource($sr),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Operation failed', 'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null], 500);
        }
    }

    public function update(UpdateServiceRequestRequest $req, ServiceRequest $serviceRequest)
    {
        try {
            $data = $req->validated();
            $data['updated_by'] = auth()->id();

            $sr = DB::transaction(function () use ($serviceRequest, $data) {
                $serviceRequest->update($data);
                $serviceRequest->logs()->create([
                    'action'      => 'updated',
                    'description' => 'Service request updated',
                    'user_id'     => auth()->id(),
                ]);
                $serviceRequest->logToEquipment(\App\Models\EquipmentLog::ACTION_UPDATED, 'Service request updated');
                return $serviceRequest->load(['branch', 'branchArea', 'assignedTo', 'equipment']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Service request updated successfully',
                'data'    => new ServiceRequestResource($sr),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Operation failed', 'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null], 500);
        }
    }

    public function destroy(ServiceRequest $serviceRequest)
    {
        $serviceRequest->delete();
        return response()->noContent();
    }

    // --- Actions ---

    public function assign(Request $req, ServiceRequest $serviceRequest)
    {
        $data = $req->validate([
            'assigned_to' => ['required', 'integer', 'exists:employees,id'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ]);

        $sr = DB::transaction(function () use ($serviceRequest, $data) {
            $serviceRequest->update(['assigned_to' => $data['assigned_to']]);
            $serviceRequest->logs()->create([
                'action'      => 'assigned',
                'description' => $data['note'] ?? 'Assigned',
                'user_id'     => auth()->id(),
            ]);
            $serviceRequest->logToEquipment(\App\Models\EquipmentLog::ACTION_UPDATED, 'Request assigned');
            return $serviceRequest->load('assignedTo');
        });

        return response()->json(['success' => true, 'message' => 'Assigned successfully', 'data' => new ServiceRequestResource($sr)]);
    }

    public function changeStatus(Request $req, ServiceRequest $serviceRequest)
    {
        $data = $req->validate([
            'status' => ['required', 'in:' .
                implode(',', array_keys(\App\Models\ServiceRequest::STATUS_LABELS))],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $sr = DB::transaction(function () use ($serviceRequest, $data) {
            $serviceRequest->update(['status' => $data['status']]);
            $serviceRequest->logs()->create([
                'action'      => 'status_changed',
                'description' => 'Status: ' . $data['status'] . ($data['note'] ? " - {$data['note']}" : ''),
                'user_id'     => auth()->id(),
            ]);
            $serviceRequest->logToEquipment(\App\Models\EquipmentLog::ACTION_UPDATED, 'Status changed: ' . $data['status']);
            return $serviceRequest;
        });

        return response()->json(['success' => true, 'message' => 'Status updated', 'data' => new ServiceRequestResource($sr)]);
    }

    public function accept(Request $req, ServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['accepted' => true]);
        $serviceRequest->logs()->create([
            'action'      => 'accepted',
            'description' => 'Request accepted',
            'user_id'     => auth()->id(),
        ]);
        return response()->json(['success' => true, 'message' => 'Accepted', 'data' => new ServiceRequestResource($serviceRequest)]);
    }

    public function attachEquipment(Request $req, ServiceRequest $serviceRequest)
    {
        $data = $req->validate(['equipment_id' => ['required', 'integer', 'exists:equipments,id']]);
        $serviceRequest->update(['equipment_id' => $data['equipment_id']]);
        $serviceRequest->logs()->create([
            'action' => 'equipment_attached',
            'description' => 'Equipment attached',
            'user_id' => auth()->id(),
        ]);
        $serviceRequest->logToEquipment(\App\Models\EquipmentLog::ACTION_UPDATED, 'Request linked');
        return response()->json(['success' => true, 'message' => 'Equipment attached', 'data' => new ServiceRequestResource($serviceRequest->load('equipment'))]);
    }

    public function detachEquipment(ServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['equipment_id' => null]);
        $serviceRequest->logs()->create([
            'action' => 'equipment_detached',
            'description' => 'Equipment detached',
            'user_id' => auth()->id(),
        ]);
        return response()->json(['success' => true, 'message' => 'Equipment detached', 'data' => new ServiceRequestResource($serviceRequest)]);
    }

    public function uploadMedia(Request $req, ServiceRequest $serviceRequest)
    {
        $req->validate(['file' => ['required', 'file', 'max:10240']]); // 10MB
        $media = $serviceRequest->addMediaFromRequest('file')->toMediaCollection('attachments');
        return response()->json(['data' => ['id' => $media->id, 'url' => $media->getUrl()]]);
    }

    // --- Comments & Logs ---

    public function comments(Request $req, ServiceRequest $serviceRequest)
    {
        $per = min((int)$req->input('per_page', 15), 100);
        $comments = $serviceRequest->comments()->with('user')->latest()->paginate($per);
        return ServiceRequestCommentResource::collection($comments);
    }

    public function addComment(Request $req, ServiceRequest $serviceRequest)
    {
        $data = $req->validate(['comment' => ['required', 'string', 'max:2000']]);
        $comment = $serviceRequest->comments()->create([
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
        ]);
        $serviceRequest->logs()->create([
            'action' => 'commented',
            'description' => mb_strimwidth($data['comment'], 0, 120, '…'),
            'user_id' => auth()->id(),
            'created_by' => auth()->id(),
        ]);
        return new ServiceRequestCommentResource($comment->load('user'));
    }

    public function logs(Request $req, ServiceRequest $serviceRequest)
    {
        $per = min((int)$req->input('per_page', 15), 100);
        $logs = $serviceRequest->logs()->latest()->paginate($per);
        return response()->json(['data' => $logs]);
    }

    public function statuses()
    {
        return response()->json([
            'data' => \App\Models\ServiceRequest::STATUS_LABELS,
        ]);
    }
}
