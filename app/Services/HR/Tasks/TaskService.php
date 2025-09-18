<?php

namespace App\Services\HR\Tasks;

use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskStep;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class TaskService
{

    public function create(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            // تطبيع بيانات افتراضية
            $data['created_by'] = $data['created_by'] ?? auth()->id();
            $data['updated_by'] = $data['updated_by'] ?? auth()->id();
            $data['task_status'] = $data['task_status'] ?? Task::STATUS_NEW;
            $data['is_daily']   = (int)($data['is_daily'] ?? 0);

            $task = Task::create($data);

            // إنشاء steps إن أُرسلت
            if (!empty($data['steps']) && is_array($data['steps'])) {
                foreach ($data['steps'] as $idx => $step) {
                    $task->steps()->create([
                        'title' => $step['title'],
                        'order' => $step['order'] ?? ($idx + 1),
                        'done'  => false,
                    ]);
                }
            }

            // Log: created
            $task->createLog(
                createdBy: auth()->id(),
                description: 'Task created',
                logType: TaskLog::TYPE_CREATED,
                details: ['status' => $task->task_status]
            );

            return $task;
        });
    }

    public function update(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update($data + ['updated_by' => auth()->id()]);
            $task->createLog(
                createdBy: auth()->id(),
                description: 'Task updated',
                logType: TaskLog::TYPE_EDITED,
                details: $data
            );
            return $task;
        });
    }

    public function move(Task $task, string $to): Task
    {
        // التحقق من الانتقالات المسموحة
        $allowed = array_keys($task->getNextStatuses()); // من نموذجك
        if (!in_array($to, $allowed)) {
            throw new InvalidArgumentException("Transition to [$to] not allowed from [$task->task_status].");
        }

        return DB::transaction(function () use ($task, $to) {
            $from = $task->task_status;
            $task->update(['task_status' => $to, 'updated_by' => auth()->id()]);

            if ($to === Task::STATUS_CLOSED) {
                $task->steps()->update(['done' => true]);
            }

            // حساب وإغلاق log السابق (total_hours_taken) يتم في createLog للنوع moved/rejected
            $task->createLog(
                createdBy: auth()->id(),
                description: "Task moved to {$to}",
                logType: TaskLog::TYPE_MOVED,
                details: ['from' => $from, 'to' => $to]
            );
            return $task;
        });
    }

    public function reject(Task $task, string $reason): Task
    {
        return DB::transaction(function () use ($task, $reason) {
            $task->update(['task_status' => Task::STATUS_REJECTED, 'updated_by' => auth()->id()]);
            $task->steps()->update(['done' => false]);

            $task->createLog(auth()->id(), 'Task rejected', TaskLog::TYPE_MOVED, ['reject_reason' => $reason]);
            $task->createLog(auth()->id(), 'Task rejected', TaskLog::TYPE_REJECTED, ['reject_reason' => $reason]);

            // كروت صفراء/حمراء حسب الإعدادات
            $count = $task->rejection_count; // accessor موجود
            if (function_exists('setting')) {
                if ($count == setting('task_rejection_times_yello_card')) {
                    $task->taskCards()->create(['type' => 'yellow', 'employee_id' => $task->assigned_to, 'active' => true]);
                }
                if ($count == setting('task_rejection_times_red_card')) {
                    $task->taskCards()->create(['type' => 'red', 'employee_id' => $task->assigned_to, 'active' => true]);
                }
            }
            return $task;
        });
    }

    public function toggleStep(Task $task, TaskStep $step): TaskStep
    {
        return DB::transaction(function () use ($task, $step) {
            // القاعدة: إذا الشخص المكلّف و الحالة new/in_progress ➜ نحرك الحالة لـ in_progress عند أول toggle
            if (
                in_array($task->task_status, [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS])
                && optional(auth()->user()->employee)->id === $task->assigned_to
            ) {
                if ($task->task_status === Task::STATUS_NEW) {
                    $this->move($task, Task::STATUS_IN_PROGRESS);
                }
            }
            $step->update(['done' => !$step->done]);
            return $step;
        });
    }

    public function attachFiles(Task $task, array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            $path = $file->store('tasks', 'public');
            $att = $task->photos()->create([
                'file_name'  => basename($path),
                'file_path'  => $path,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $stored[] = $att;
        }
        $task->createLog(auth()->id(), 'Images added', TaskLog::TYPE_IMAGES_ADDED, ['count' => count($stored)]);
        return $stored;
    }
}
