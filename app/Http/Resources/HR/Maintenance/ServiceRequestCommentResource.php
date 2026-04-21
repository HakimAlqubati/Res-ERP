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
            'created_at' =>  \Carbon\Carbon::parse($this->created_at)->format('Y-m-d H:i:s'),
            'images'     => $this->getMedia('attachments')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                    'name' => $media->file_name,
                ];
            }),
        ];
    }
}
