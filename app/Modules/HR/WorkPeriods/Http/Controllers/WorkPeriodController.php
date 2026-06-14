<?php

namespace App\Modules\HR\WorkPeriods\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\WorkPeriods\Http\Requests\StoreWorkPeriodRequest;
use App\Modules\HR\WorkPeriods\Http\Requests\UpdateWorkPeriodRequest;
use App\Modules\HR\WorkPeriods\Http\Resources\WorkPeriodResource;
use App\Modules\HR\WorkPeriods\Services\WorkPeriodService;

class WorkPeriodController extends Controller
{
    protected $service;

    public function __construct(WorkPeriodService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $workPeriods = $this->service->getAll();
        return WorkPeriodResource::collection($workPeriods);
    }

    public function store(StoreWorkPeriodRequest $request)
    {
        $workPeriod = $this->service->create($request->validated());
        return new WorkPeriodResource($workPeriod);
    }

    public function show($id)
    {
        $workPeriod = $this->service->getById($id);
        return new WorkPeriodResource($workPeriod);
    }

    public function update(UpdateWorkPeriodRequest $request, $id)
    {
        $workPeriod = $this->service->update($id, $request->validated());
        return new WorkPeriodResource($workPeriod);
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return response()->json(['message' => 'Work period deleted successfully']);
    }
}
