<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;

use App\DTOs\HR\Payroll\RunPayrollData;
use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use App\Models\PayrollRun;
use App\Services\HR\Payroll\PayrollRunService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Parse "July 2025" coming in as $data['name'] (month label)
        [$monthName, $year] = explode(' ', $data['name']);
        $monthNumber = Carbon::parse($monthName)->month;

        // Period (if you have custom month logic, keep your helper)
        $monthData = getEndOfMonthDate((int) $year, (int) $monthNumber);
        $data['period_start_date'] = $monthData['start_month'];
        $data['period_end_date']   = $monthData['end_month'];

        // Normalize (your service expects numeric year/month)
        $data['year']  = (int) $year;
        $data['month'] = (int) $monthNumber;

        // Auto title (used for display only; service builds/updates too)
        $data['name'] = sprintf(
            'Payroll %s (Branch %d)',
            Carbon::createFromDate((int) $year, (int) $monthNumber, 1)->format('Y-m'),
            (int) $data['branch_id']
        );

        // Clean transient fields if present
        unset($data['note_that'], $data['month_choice']);
 
        return $data;
    }

    /**
     * Instead of default Eloquent create, delegate to PayrollRunService::runAndPersist()
     * and return the created/updated PayrollRun model.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        DB::beginTransaction();
        try {
            //code...

            /** @var PayrollRunService $service */
            $service = app(PayrollRunService::class);

            // Build DTO expected by your service
            $dto = new RunPayrollData(
                branchId: (int) $data['branch_id'],
                year: (int) $data['year'],
                month: (int) $data['month'],
                overwriteExisting: false,
                payDate: $data['pay_date'] ?? null,
            );

            $result = $service->runAndPersist($dto);

            if ($result['success']) {
                // showSuccessNotifiMessage($result['message']);
                DB::commit();
                $this->getRedirectUrl();
                return PayrollRun::findOrFail($result['meta']['payroll_run_id']);
            } else {
                showWarningNotifiMessage($result['message']);
                $this->halt();
            }


            // Return the PayrollRun for Filament to redirect to View page
            return PayrollRun::findOrFail($result['meta']['payroll_run_id']);
        } catch (\Throwable $th) {
            Notification::make()
                ->title('An unexpected error occurred')
                ->body($th->getMessage())
                ->danger()
                ->send();

            DB::rollBack();
            $this->halt();
        }
        return new PayrollRun();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
