<?php

namespace App\Modules\HR\WorkPeriods\Services;

use App\Models\WorkPeriod;
use App\Modules\HR\WorkPeriods\Repositories\WorkPeriodRepositoryInterface;

class WorkPeriodService
{
    protected $repository;

    public function __construct(WorkPeriodRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(array $filters = [])
    {
        return $this->repository->getAll($filters);
    }

    public function getById($id)
    {
        return $this->repository->getById($id);
    }

    public function create(array $data)
    {
        $data['days'] = json_encode(['sun']);
        if (auth()->check()) {
            $data['created_by'] = auth()->user()->id;
            $data['updated_by'] = auth()->user()->id;
        }
        $data['day_and_night'] = WorkPeriod::calculateDayAndNight($data['start_at'], $data['end_at']);
        
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        if (auth()->check()) {
            $data['updated_by'] = auth()->user()->id;
        }
        if (isset($data['start_at']) && isset($data['end_at'])) {
            $data['day_and_night'] = WorkPeriod::calculateDayAndNight($data['start_at'], $data['end_at']);
        }
        
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }
}
