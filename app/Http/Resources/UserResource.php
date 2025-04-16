<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'owner_id' => $this->owner_id,
            'role_id' => $this->roles[0]->id,
            'fcm_token' => $this->fcm_token,
            'branch_is_central_kitchen' => (int) ($this->branch->is_kitchen ?? 0),
            'branch' => $this->branch,
            'roles' => $this->roles->pluck('name', 'id'),
            'login_auth_type' => setting('login_auth_type'),
            'login_method' => setting('login_method'),
            'branches' => $this
                ->manageBranches()
                ->activePopups()
                ->select('id', 'name', 'type', 'start_date', 'end_date')
                ->get(),
        ];
    }
}
