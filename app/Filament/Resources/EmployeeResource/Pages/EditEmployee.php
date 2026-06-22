<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\EmployeeActions;
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
            EmployeeActions::attendance(),
            RestoreAction::make(),
            \Filament\Actions\Action::make('rehire')
                ->label(__('lang.rehire'))
                ->color('success')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn($record) => !$record->active)
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('join_date')
                        ->label(__('lang.join_date'))
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label(__('lang.notes')),
                ])
                ->action(function (\App\Models\Employee $record, array $data) {
                    try {
                        app(\App\Modules\HR\Employee\Services\EmployeeLifecycleService::class)->rehire($record, $data);

                        \Filament\Notifications\Notification::make()
                            ->title(__('lang.employee_rehired_successfully'))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('lang.error_occurred'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if(isHakimOrAdel()){
            return EmployeeResource::getUrl('edit',['record'=>$this->record->id]);
        }
        return $this->getResource()::getUrl('index');
    }

    public function afterSave()
    {
        $settingsData = $this->data['settings'] ?? [];

        if (!empty($settingsData)) {
            $this->record->settings()->updateOrCreate(
                ['employee_id' => $this->record->id],
                $settingsData
            );
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // dd($data['employee_periods'],$this->record->id);
        $this->logPeriodChanges();

        // Remove settings data — will be saved in afterSave
        unset($data['settings']);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $terminationData = $this->record?->serviceTermination ?? null;
        $data['termination_date'] = $terminationData?->termination_date;
        $data['termination_reason'] = $terminationData?->termination_reason;

        // Load settings from the related table
        $settings = $this->record?->settings;
        $data['settings'] = [
            'can_view_all_branches' => $settings?->can_view_all_branches ?? false,
        ];

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
