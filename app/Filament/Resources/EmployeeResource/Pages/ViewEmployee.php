<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\EmployeeActions;
use App\Filament\Resources\UserResource;
use App\Models\Employee;
use App\Models\EmployeeServiceTermination;
use App\Modules\HR\Employee\Services\EmployeeLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $terminationData = $this->record?->serviceTermination ?? null;
        $data['termination_date'] = $terminationData?->termination_date;
        $data['termination_reason'] = $terminationData?->termination_reason;
        $data['notes'] = $terminationData?->notes;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make(),
            DeleteAction::make()
                ->visible(
                    fn () => EmployeeResource::canDeleteAny()
                      && EmployeeResource::canDelete($this->record)
                ),
            EmployeeActions::changeBranch(),
            EmployeeActions::changeSalary(),
            EmployeeActions::active(),
            EmployeeActions::inactive(),

            EmployeeActions::rehire(
                fn (Employee $record): bool => ! $record->active && $record->serviceTermination?->status === EmployeeServiceTermination::STATUS_APPROVED
            ),
            EmployeeActions::attendance(),

            

            Action::make('show_user_page')
                ->label(__('User Page'))
                ->color('gray')
                ->icon('heroicon-o-user')
                ->url(fn ($record) => $record->user_id ? UserResource::getUrl('edit', ['record' => $record->user_id]) : null)
                ->visible(fn ($record) => $record->user_id !== null)
                ->openUrlInNewTab(),

        ];
    }
}
