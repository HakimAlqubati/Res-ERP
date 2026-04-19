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
use Illuminate\Support\Facades\Storage;

class ServiceRequestController extends Controller
{
    public function index(Request $req)
    {
        $q = ServiceRequest::query()
            ->with(['branch', 'branchArea', 'assignedTo', 'equipment'])
            ->when($req->filled('search'), function ($x) use ($req) {
                $v = $req->input('search');
                $x->where(function ($q) use ($v) {
                    $q->where('description', 'like', "%{$v}%")
                        ->orWhere('id', $v);
                });
            })
            ->when($req->filled('status'), fn($x) => $x->where('status', $req->input('status')))
            ->when($req->filled('urgency'), fn($x) => $x->where('urgency', $req->input('urgency')))
            ->when($req->filled('impact'),  fn($x) => $x->where('impact',  $req->input('impact')))
            ->when($req->filled('branch_id'), fn($x) => $x->where('branch_id', $req->input('branch_id')))
            ->when($req->has('assigned_to'), function ($x) use ($req) {
                $v = $req->input('assigned_to');
                if ($v === 'unassigned' || $v === 'null') {
                    $x->whereNull('assigned_to');
                } elseif ($v !== null && $v !== '') {
                    $x->where('assigned_to', $v);
                }
            })
            ->when($req->filled('equipment_id'), fn($x) => $x->where('equipment_id', $req->input('equipment_id')));

        // sort
        $sort = $req->input('sort', '-created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['created_at', 'status', 'urgency', 'impact', 'branch_id'];
        if (! in_array($col, $allowed)) $col = 'created_at';
        $q->orderBy($col, $dir);

        $per = min((int) $req->input('per_page', 15), 100);
        $paginator = $q->paginate($per);

        $resource = ServiceRequestResource::collection($paginator)->response()->getData(true);

        return response()->json(array_merge(
            ['count' => $paginator->total()],
            $resource
        ));
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
                    'log_type'    => ServiceRequestLog::LOG_TYPE_CREATED,
                    'description' => 'Service request created',
                    'created_by'  => auth()->id(),
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
                    'log_type'    => ServiceRequestLog::LOG_TYPE_UPDATED,
                    'description' => 'Service request updated',
                    'created_by'  => auth()->id(),
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
            'assigned_to' => ['required', 'integer', 'exists:hr_employees,id'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ]);

        $sr = DB::transaction(function () use ($serviceRequest, $data) {
            $serviceRequest->update(['assigned_to' => $data['assigned_to']]);
            $serviceRequest->logs()->create([
                'log_type'    => ServiceRequestLog::LOG_TYPE_REASSIGN_TO_USER,
                'description' => $data['note'] ?? 'Assigned',
                'created_by'  => auth()->id(),
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
                'log_type'    => ServiceRequestLog::LOG_TYPE_STATUS_CHANGED,
                'description' => 'Status: ' . $data['status'] . ($data['note'] ? " - {$data['note']}" : ''),
                'created_by'  => auth()->id(),
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
            'log_type'    => ServiceRequestLog::LOG_TYPE_UPDATED,
            'description' => 'Request accepted',
            'created_by'  => auth()->id(),
        ]);
        return response()->json(['success' => true, 'message' => 'Accepted', 'data' => new ServiceRequestResource($serviceRequest)]);
    }

    public function attachEquipment(Request $req, ServiceRequest $serviceRequest)
    {
        $data = $req->validate(['equipment_id' => ['required', 'integer', 'exists:equipments,id']]);
        $serviceRequest->update(['equipment_id' => $data['equipment_id']]);
        $serviceRequest->logs()->create([
            'log_type'    => ServiceRequestLog::LOG_TYPE_UPDATED,
            'description' => 'Equipment attached',
            'created_by'  => auth()->id(),
        ]);
        $serviceRequest->logToEquipment(\App\Models\EquipmentLog::ACTION_UPDATED, 'Request linked');
        return response()->json(['success' => true, 'message' => 'Equipment attached', 'data' => new ServiceRequestResource($serviceRequest->load('equipment'))]);
    }

    public function detachEquipment(ServiceRequest $serviceRequest)
    {
        $serviceRequest->update(['equipment_id' => null]);
        $serviceRequest->logs()->create([
            'log_type'    => ServiceRequestLog::LOG_TYPE_UPDATED,
            'description' => 'Equipment detached',
            'created_by'  => auth()->id(),
        ]);
        return response()->json(['success' => true, 'message' => 'Equipment detached', 'data' => new ServiceRequestResource($serviceRequest)]);
    }

    public function uploadMedia(Request $req, ServiceRequest $serviceRequest)
    {
        $req->validate(['file' => ['required', 'file', 'max:10240']]); // 10MB
        $file = $req->file('file');

        // إذا كان الملف صورة → ضغطه، وإلا ارفعه كما هو
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $media = compressAndAddImage($serviceRequest, $file, 'attachments');
        } else {
            $media = $serviceRequest->addMediaFromRequest('file')->toMediaCollection('attachments');
        }
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
        $data = $req->validate([
            'comment' => ['required', 'string', 'max:2000'],
            'images'  => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:10240'],
        ]);

        $comment = $serviceRequest->comments()->create([
            'comment' => $data['comment'],
            'created_by' => auth()->id(),
        ]);

        if ($req->hasFile('images')) {
            foreach ($req->file('images') as $image) {
                $comment->addMedia($image)->toMediaCollection('attachments');
            }
        }

        $serviceRequest->logs()->create([
            'log_type'    => \App\Models\ServiceRequestLog::LOG_TYPE_COMMENT_ADDED,
            'description' => mb_strimwidth($data['comment'], 0, 120, '…'),
            'created_by'  => auth()->id(),
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

    public function getPhotos(ServiceRequest $serviceRequest)
    {
        $photos = $serviceRequest->getMedia('attachments');

        $data = $photos->map(fn($m) => [
            'id'        => $m->id,
            'name'      => $m->name,
            'file_name' => $m->file_name,
            'mime_type' => $m->mime_type,
            'size'      => $m->size,
            'url'       => $m->getFullUrl(),
            'created_at' => $m->created_at,
        ]);

        return response()->json([
            'success' => true,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }
}
