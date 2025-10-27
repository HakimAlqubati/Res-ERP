<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\{StoreTaskRequest, UpdateTaskRequest, MoveTaskRequest, RejectTaskRequest};
use App\Http\Resources\HR\TaskResource;
use App\Models\Task;
use App\Services\HR\Tasks\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function __construct(private TaskService $service) {}

    public function index(Request $req)
    {
        $q = Task::query()->where('is_daily', 0);

        // فلاتر أساسية
        if ($status = $req->query('status'))  $q->whereIn('task_status', (array)$status);
        if ($branch = $req->query('branch_id')) $q->where('branch_id', $branch);
        if ($assignedTo = $req->query('assigned_to')) $q->where('assigned_to', $assignedTo);
        if ($search = $req->query('q')) $q->where('title', 'like', "%$search%");

        // ترتيب
        $q->orderByDesc('id');
        // dd($q->get());
        return TaskResource::collection($q->paginate($req->integer('per_page', 15)));
    }

    public function store(StoreTaskRequest $request)
    {
        // $this->authorize('create', Task::class);
        $task = $this->service->create($request->validated());
        return new TaskResource($task);
    }

    public function show(Task $task)
    {
        // $this->authorize('view', $task);
        return new TaskResource($task->load(['assigned', 'assignedby', 'createdby']));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        // $this->authorize('update', $task);
        $task = $this->service->update($task, $request->validated());
        return new TaskResource($task);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function move(MoveTaskRequest $request, Task $task)
    {
        // $this->authorize('move', $task);
        $task = $this->service->move($task, $request->string('to'));
        return new TaskResource($task);
    }

    public function reject(RejectTaskRequest $request, Task $task)
    {
        // $this->authorize('reject', $task);
        $task = $this->service->reject($task, $request->string('reject_reason'));
        return new TaskResource($task);
    }

    /**
     * GET /tasks/statuses
     * List all possible task statuses
     */
    public function getStatuses()
    {
        return response()->json([
            'statuses' =>  [
                Task::STATUS_NEW,   
                Task::STATUS_IN_PROGRESS,
                Task::STATUS_CLOSED,
                Task::STATUS_REJECTED,
            ]
        ]);
    }
    public function getStatusColors()
    {
        return response()->json([
            'statuses' => Task::getStatusColors()
        ]);
    }

    /**
     * GET /tasks/{task}/next-statuses
     * Get next possible statuses for a specific task
     */
    public function getNextStatuses(Task $task)
    {
        return response()->json([
            'task_id' => $task->id,
            'current_status' => $task->task_status,
            'next_statuses' => $task->getNextStatuses()
        ]);
    }

    public function storePhotos(Request $request, Task $task)
    {
        $request->validate([
            'photos'   => 'required|array',
            'photos.*' => 'file|image|max:5120', // 5MB
        ]);

        DB::beginTransaction();
        try {
            $uploaded = [];

            foreach ($request->file('photos') as $file) {
                /** @var Media $media */
                $media = $task->addMedia($file)->toMediaCollection('attachments');
                $uploaded[] = [
                    'id'        => $media->id,
                    'name'      => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size'      => $media->size,
                    'url'       => $media->getFullUrl(),
                    // 'thumb'   => $media->hasGeneratedConversion('thumb') ? $media->getFullUrl('thumb') : null,
                    'created_at' => $media->created_at,
                ];
            }

            // لوج اختياري
            $task->createLog(
                auth()->id(),
                'Added task photos',
                \App\Models\TaskLog::TYPE_IMAGES_ADDED,
                ['count' => count($uploaded)]
            );

            DB::commit();

            return response()->json([
                'message' => 'Photos uploaded successfully',
                'photos'  => $uploaded,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Upload failed',
                'error'   => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
            ], 500);
        }
    }
}
