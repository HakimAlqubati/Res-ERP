<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'comment_id' => $this->id,
            'comment' => $this->comment,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user_id,
                'avatar' => $this->user->avatar_image,
                'name' => $this->user->name
            ],
            'task_id' => $this->task_id,
        ];
    }
}
