<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\EmployeeActions;
use App\Filament\Resources\UserResource;
use App\Models\Employee;
use Filament\Actions\ViewAction;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ViewAction::make(),
            \App\Filament\Resources\EmployeeResource\EmployeeActions::changeBranch(),
            EmployeeActions::changeSalary(),
            EmployeeActions::attendance(),
            RestoreAction::make(),
            EmployeeActions::rehire(),
                \Filament\Actions\Action::make('show_user_page')
                ->label(__('User Page'))
                ->color('gray')
                ->icon('heroicon-o-user')
                ->url(fn ($record) => $record->user_id ? UserResource::getUrl('edit', ['record' => $record->user_id]) : null)
                ->visible(fn ($record) => $record->user_id !== null)
                ->openUrlInNewTab(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if(isHakimOrAdel()){
            return EmployeeResource::getUrl('edit',['record'=>$this->record->id]);
        }
        return $this->getResource()::getUrl('index');
    }

    public function afterSave() {}

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // dd($data['employee_periods'],$this->record->id);
        $this->logPeriodChanges();
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $terminationData = $this->record?->serviceTermination ?? null;
        $data['termination_date'] = $terminationData?->termination_date;
        $data['termination_reason'] = $terminationData?->termination_reason;
        return $data;
    }
    protected function logPeriodChanges()
    {
        // Get the employee being edited
        $employee = Employee::find($this->record->id);

        // Get previous and current period IDs
        $previousPeriods = $employee?->periods?->pluck('id')->toArray();
        $currentPeriods = $this?->data['periods'] ?? [];
        if (count($currentPeriods)) {

            // Determine added and removed periods
            $addedPeriods = array_diff($currentPeriods, $previousPeriods);
            $removedPeriods = array_diff($previousPeriods, $currentPeriods);

            // Log added periods
            if (!empty($addedPeriods)) {
                $employee->logPeriodChange($addedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_ADDED);
            }

            // Log removed periods
            if (!empty($removedPeriods)) {
                $employee->logPeriodChange($removedPeriods, Employee::TYPE_ACTION_EMPLOYEE_PERIOD_LOG_REMOVED);
            }
        }
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
