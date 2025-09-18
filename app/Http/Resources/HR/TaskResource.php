<?php

namespace App\Http\Resources\HR;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                 => $this->id,
            'title'              => $this->title,
            'description'        => $this->description,
            'status'             => $this->task_status,
            'status_color'       => Task::getStatusColors()[$this->task_status] ?? null,
            'assigned_to'        => optional($this->assigned)->only(['id', 'name']),
            'assigned_by'        => optional($this->assignedby)->only(['id', 'name']),
            'created_by'         => optional($this->createdby)->only(['id', 'name']),
            'due_date'           => optional($this->due_date)?->format('Y-m-d'),
            'branch_id'          => $this->branch_id,
            'views'              => $this->views,
            'photos_count'       => $this->photos_count,
            'step_count'         => $this->step_count,
            'progress_percentage' => $this->progress_percentage,
            'rejection_count'    => $this->rejection_count,
            'total_spent_time'   => $this->total_spent_time,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
            // يمكن ضم Steps/Logs بشكل منفصل عبر endpoints خاصة
            '_links' => [
                'self'       => route('tasks.show', $this->id),
                // 'steps'      => route('tasks.steps.index', $this->id),
                // 'comments'   => route('tasks.comments.index', $this->id),
                // 'attachments' => route('tasks.attachments.index', $this->id),
                // 'logs'       => route('tasks.logs.index', $this->id),
            ],
        ];
    }
}
