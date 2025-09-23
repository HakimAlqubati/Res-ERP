<?php 
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\ {StoreTaskRequest, UpdateTaskRequest, MoveTaskRequest, RejectTaskRequest, ToggleStepRequest};
use App\Http\Resources\HR\TaskResource;
use App\Http\Resources\HR\TaskStepResource;
use App\Models\Task;
use App\Models\TaskStep;
use App\Services\HR\Tasks\TaskService;
use Illuminate\Http\Request;
class TaskStepController extends Controller {
  public function __construct(private TaskService $service){}

  public function index(Task $task) {
    // $this->authorize('view', $task);
    return TaskStepResource::collection($task->steps()->orderBy('order')->get());
  }

  public function toggleDone(ToggleStepRequest $req, Task $task, TaskStep $step) {
    // $this->authorize('update', $task);
    abort_unless($step->morphable_id === $task->id && $step->morphable_type === Task::class, 404);
    $step = $this->service->toggleStep($task,$step);
    return new TaskStepResource($step);
  }
}
