<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\EmployeeResource;
use App\Models\Employee;
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
