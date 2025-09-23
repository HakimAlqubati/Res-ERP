<?php

namespace App\Http\Resources\HR;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskStepResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'step_id' => $this->id,
            'task_id' => $this->morphable_id,
            'title' => $this->title,
            'order' => $this->order,
            'done' => $this->done,
        ];
    }
}
