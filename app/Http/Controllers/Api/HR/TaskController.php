<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\{StoreTaskRequest, UpdateTaskRequest, MoveTaskRequest, RejectTaskRequest};
use App\Http\Resources\HR\TaskResource;
use App\Models\Task;
use App\Services\HR\Tasks\TaskService;
use Illuminate\Http\Request;

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
            'statuses' => Task::getStatuses()
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
}
