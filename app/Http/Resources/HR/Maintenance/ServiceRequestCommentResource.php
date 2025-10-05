<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestCommentResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $this->user;
        return [
            'id'        => $this->id,
            'comment'   => $this->comment,
            'user'      => ['id' => $user?->id, 'name' => $user?->name],
            'created_at' => $this->created_at,
        ];
    }
}
