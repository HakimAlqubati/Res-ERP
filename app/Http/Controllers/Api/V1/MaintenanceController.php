<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\Maintenance\EquipmentResource;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function overdue(Request $req, \App\Services\Warnings\Support\MaintenanceRepository $repo)
    {
        $data = $repo->overdue($this->filters($req));
        return EquipmentResource::collection($data);
    }

    public function dueSoon(Request $req, \App\Services\Warnings\Support\MaintenanceRepository $repo)
    {
        $days = (int)$req->input('days', 7);
        $data = $repo->dueWithin($days, $this->filters($req));
        return EquipmentResource::collection($data);
    }

    public function summary(Request $req, \App\Services\Warnings\Support\MaintenanceRepository $repo)
    {
        $days = (int)$req->input('days', 7);
        $overdue = $repo->overdue($this->filters($req))->count();
        $due = $repo->dueWithin($days, $this->filters($req))->count();

        return response()->json([
            'data' => [
                'overdue_count' => $overdue,
                'due_'. $days .'d_count' => $due,
            ],
            'meta' => ['days' => $days],
        ]);
    }

    protected function filters(Request $req): array
    {
        return [
            'branch_id' => $req->input('filter.branch_id'),
            'branch_area_id' => $req->input('filter.branch_area_id'),
            'status' => $req->input('filter.status'),
        ];
    }
}
