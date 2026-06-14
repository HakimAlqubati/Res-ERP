<?php

namespace App\Modules\HR\WorkPeriods\Repositories;

use App\Models\WorkPeriod;

class WorkPeriodRepository implements WorkPeriodRepositoryInterface
{
    public function getAll()
    {
        return WorkPeriod::with('branch')->orderBy('id', 'desc')->get();
    }

    public function getById($id)
    {
        return WorkPeriod::with('branch')->findOrFail($id);
    }

    public function create(array $data)
    {
        return WorkPeriod::create($data);
    }

    public function update($id, array $data)
    {
        $workPeriod = $this->getById($id);
        $workPeriod->update($data);
        return $workPeriod;
    }

    public function delete($id)
    {
        $workPeriod = $this->getById($id);
        return $workPeriod->delete();
    }
}
