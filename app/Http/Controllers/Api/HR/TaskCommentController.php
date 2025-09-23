<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\{StoreTaskRequest, UpdateTaskRequest, MoveTaskRequest, RejectTaskRequest, ToggleStepRequest};
use App\Http\Resources\HR\TaskCommentResource;
use App\Http\Resources\HR\TaskResource;
use App\Models\Task;
use App\Models\TaskStep;
use App\Services\HR\Tasks\TaskService;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
  public function __construct(private TaskService $service) {}

  public function index(Task $task)
  {
    // $this->authorize('view', $task);
    return TaskCommentResource::collection($task->comments()->get());
  }

  public function store(Request $request, Task $task)
  {
    // تحقق من صحة البيانات
    $request->validate([
      'comment' => 'required|string|max:1000',
    ]);

    // إنشاء التعليق
    $comment = $task->comments()->create([
      'user_id' => auth()->id(),
      'comment' => $request->comment,
    ]);

    // يمكنك تسجيله في اللوج إذا أحببت
    $task->createLog(auth()->id(), 'Added a comment', \App\Models\TaskLog::TYPE_COMMENTED, [
      'comment_id' => $comment->id
    ]);

    // إرجاع التعليق الجديد كـ Resource
    return new TaskCommentResource($comment);
  }
}
