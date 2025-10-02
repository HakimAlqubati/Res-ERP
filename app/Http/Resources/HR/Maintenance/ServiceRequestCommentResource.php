<?php

namespace App\Http\Resources\HR\Maintenance;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestCommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'comment'   => $this->comment,
            'user'      => $this->whenLoaded('user'),
            'created_at'=> $this->created_at,
        ];
    }
}
