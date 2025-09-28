<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\HR\Maintenance\EquipmentResource;
use App\Http\Requests\HR\Maintenance\StoreEquipmentRequest;
use App\Http\Requests\HR\Maintenance\UpdateEquipmentRequest;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $req)
    {
        $q = Equipment::query()
            ->with(['type.category', 'branch'])
            ->when($req->input('filter.search'), fn($x, $v) => $x->where(function ($q) use ($v) {
                $q->where('name', 'like', "%$v%")
                    ->orWhere('asset_tag', 'like', "%$v%")
                    ->orWhere('serial_number', 'like', "%$v%");
            }))
            ->when($req->input('filter.status'), fn($x, $v) => $x->where('status', $v))
            ->when($req->input('filter.type_id'), fn($x, $v) => $x->where('type_id', $v))
            ->when($req->input('filter.category_id'), fn($x, $v) => $x->whereHas('type', fn($qq) => $qq->where('category_id', $v)))
            ->when($req->input('filter.branch_id'), fn($x, $v) => $x->where('branch_id', $v))
            ->when($req->input('filter.branch_area_id'), fn($x, $v) => $x->where('branch_area_id', $v));

        // sort
        $sort = $req->input('sort', '-created_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['created_at', 'name', 'next_service_date'];
        if (!in_array($col, $allowed)) {
            $col = 'created_at';
        }
        $q->orderBy($col, $dir);

        $per = min((int)$req->input('per_page', 15), 100);
        return EquipmentResource::collection($q->paginate($per));
    }

    public function show(Equipment $equipment)
    {
        return new EquipmentResource(
            $equipment->load(['type.category', 'branch'])
        );
    }


    public function store(StoreEquipmentRequest $req)
    {
        $data = $req->validated();

        // توليد asset_tag (نفس منطق Filament Resource لديك)
        if (empty($data['asset_tag'])) {
            $data['asset_tag'] = app(\App\Services\HR\Maintenance\AssetTagGenerator::class)->generate(
                $data['type_id']
            );
        }

        $eq = Equipment::create($data);
        $eq->addLog(\App\Models\EquipmentLog::ACTION_CREATED, 'Created via API');

        return (new EquipmentResource($eq->load(['type.category', 'branch'])))->response()->setStatusCode(201);
    }

    public function update(UpdateEquipmentRequest $req, Equipment $equipment)
    {
        $equipment->update($req->validated());
        $equipment->addLog(\App\Models\EquipmentLog::ACTION_UPDATED, 'Updated via API');
        return new EquipmentResource($equipment->load(['type.category', 'branch']));
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->delete();
        return response()->noContent();
    }

    public function service(Request $req, Equipment $equipment)
    {
        $validated = $req->validate([
            'serviced_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $servicedAt = new \Carbon\Carbon($validated['serviced_at'] ?? now());
        $equipment->last_serviced = $servicedAt->toDateString();

        // احسب القادم بناءً على service_interval_days إن وجد
        if ($equipment->service_interval_days) {
            $equipment->next_service_date = $servicedAt->clone()->addDays((int)$equipment->service_interval_days)->toDateString();
        }

        $equipment->save();
        $equipment->addLog(\App\Models\EquipmentLog::ACTION_SERVICED, $validated['description'] ?? 'Serviced', auth()->id());

        return new EquipmentResource($equipment);
    }

    public function move(Request $req, Equipment $equipment)
    {
        $data = $req->validate([
            'branch_id' => ['required', 'integer'],
            'branch_area_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
        $equipment->update([
            'branch_id' => $data['branch_id'],
            'branch_area_id' => $data['branch_area_id'] ?? null,
        ]);
        $equipment->addLog(\App\Models\EquipmentLog::ACTION_MOVED, $data['description'] ?? 'Moved', auth()->id());
        return new EquipmentResource($equipment->load('branch'));
    }

    public function retire(Request $req, Equipment $equipment)
    {
        $data = $req->validate(['description' => ['nullable', 'string', 'max:1000']]);
        $equipment->update(['status' => \App\Models\Equipment::STATUS_RETIRED]);
        $equipment->addLog(\App\Models\EquipmentLog::ACTION_RETIRED, $data['description'] ?? 'Retired', auth()->id());
        return new EquipmentResource($equipment);
    }

    public function uploadMedia(Request $req, Equipment $equipment)
    {
        $req->validate(['file' => ['required', 'file', 'max:10240']]); // 10MB
        $media = $equipment->addMediaFromRequest('file')->toMediaCollection('attachments');
        return response()->json(['data' => ['id' => $media->id, 'url' => $media->getUrl()]]);
    }
}
