<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\Maintenance\EquipmentLogResource;
use Illuminate\Http\Request;

class EquipmentLogController extends Controller
{
    public function index(Request $req)
    {
        $q = \App\Models\EquipmentLog::query()
            ->when($req->input('equipment_id'), fn($x,$v)=>$x->where('equipment_id',$v))
            ->when($req->input('action'), fn($x,$v)=>$x->where('action',$v))
            ->when($req->input('from'), fn($x,$v)=>$x->where('created_at','>=',$v))
            ->when($req->input('to'), fn($x,$v)=>$x->where('created_at','<=',$v))
            ->orderByDesc('created_at');

        return EquipmentLogResource::collection($q->paginate(min((int)$req->input('per_page',15),100)));
    }

    public function byEquipment(\App\Models\Equipment $equipment)
    {
        return EquipmentLogResource::collection($equipment->logs()->orderByDesc('created_at')->paginate(15));
    }

    public function store(Request $req, \App\Models\Equipment $equipment)
    {
        $data = $req->validate([
            'action' => ['required','in:Created,Updated,Serviced,Moved,Retired'],
            'description' => ['nullable','string','max:1000'],
        ]);
        $equipment->addLog($data['action'], $data['description'] ?? null, auth()->id());
        return response()->json(['message'=>'logged'], 201);
    }
}
