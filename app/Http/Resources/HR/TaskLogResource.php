<?php

namespace App\Http\Resources\HR;

use App\Models\Task;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'created_by' =>[
                'id' =>  $this->created_by,
                'name' => $this->creator?->name,
                'avatar' => $this->creator?->avatar_image
            ],
            'description' => $this->description,
            'log_type' => $this->log_type,
            'details' => $this->details ? json_decode($this->details, true) : null,
            'total_hours_taken' => $this->total_hours_taken,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // إذا أردت إضافة اسم المستخدم الذي أنشأ اللوج:
            
        ];
    }
}
