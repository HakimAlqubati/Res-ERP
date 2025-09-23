<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\{StoreTaskRequest, UpdateTaskRequest, MoveTaskRequest, RejectTaskRequest, ToggleStepRequest};
use App\Http\Resources\HR\TaskCommentResource;
use App\Http\Resources\HR\TaskLogResource;
use App\Models\Task;
use App\Models\TaskStep;
use App\Services\HR\Tasks\TaskService;
use Illuminate\Http\Request;

class TaskLogController extends Controller
{
  public function __construct(private TaskService $service) {}

  public function index(Task $task)
  {
    // $this->authorize('view', $task);
    return TaskLogResource::collection($task->logs()->get());
  }
}
