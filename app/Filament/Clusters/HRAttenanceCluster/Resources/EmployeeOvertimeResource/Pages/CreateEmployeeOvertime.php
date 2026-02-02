<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Pages;

use Exception;
use App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource;
use App\Models\EmployeeOvertime;
use App\Services\HR\MonthClosure\MonthClosureService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEmployeeOvertime extends CreateRecord
{
    protected static string $resource        = EmployeeOvertimeResource::class;
    protected ?bool $hasDatabaseTransactions = true;



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        try {
            app(MonthClosureService::class)->ensureMonthIsOpen($data['date']);

            $service = app(\App\Modules\HR\Overtime\OvertimeService::class);

            switch ($data['type']) {
                case EmployeeOvertime::TYPE_BASED_ON_DAY:
                    $create = $service->handleOvertimeByDay($data);
                    if ($create) {
                        showSuccessNotifiMessage('Done');
                        $this->redirect(static::getResource()::getUrl('index'));
                        return;
                    }
                    break;
                case EmployeeOvertime::TYPE_BASED_ON_MONTH:
                    $create = $service->handleOverTimeMonth($data);
                    if ($create) {
                        showSuccessNotifiMessage('Done');
                        $this->redirect(static::getResource()::getUrl('index'));
                        return;
                    }
                    break;

                default:
                    # code...
                    break;
            }

            $this->getRedirectUrl();
        } catch (\Exception $th) {
            showWarningNotifiMessage($th->getMessage());
            // throw $th;
        }
    }
}
