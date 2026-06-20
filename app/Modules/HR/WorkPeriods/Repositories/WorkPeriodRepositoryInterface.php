<?php

namespace App\Modules\HR\WorkPeriods\Repositories;

use App\Models\WorkPeriod;

interface WorkPeriodRepositoryInterface
{
    public function getAll(array $filters = []);
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
