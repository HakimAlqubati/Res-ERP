<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'platform'              => $this->platform,
            'version_name'          => $this->version_name,
            'version_code'          => $this->version_code,
            'min_supported_version' => $this->min_supported_version,
            'min_version_code'      => $this->min_version_code,
            'is_force_update'       => (bool) $this->is_force_update,
            'download_url'          => $this->download_url,
            'release_notes'         => $this->release_notes,
            'is_active'             => (bool) $this->is_active,
            'created_by'            => $this->created_by,
            'creator_name'          => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at'            => $this->created_at?->toDateTimeString(),
            'updated_at'            => $this->updated_at?->toDateTimeString(),
        ];
    }
}
