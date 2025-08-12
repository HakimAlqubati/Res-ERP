<?php

namespace App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PayrollResource;
use App\Filament\Pages\RunPayroll;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('runWizard')
            //     ->label('Run Payroll')
            //     ->url(
            //         RunPayroll::getUrl()

            //     )
            //     ->icon('heroicon-o-play'),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    // Handlers referenced by table actions
    public function resimulateRecord($record): void
    {
        // Dispatch a job to re-simulate this single payroll
        // ...
        // $this->notify('success', 'Re-simulation triggered.');
    }

    public function approveRecord($record): void
    {
        $record->update(['status' => 'approved']);
        // $this->notify('success', 'Payroll approved.');
    }

    public function bulkApproveAction(array $records): void
    {
        foreach ($records as $record) {
            $record->update(['status' => 'approved']);
        }
        // $this->notify('success', 'Selected payrolls approved.');
    }

    public function bulkGenerateTransactions(array $records): void
    {
        // Dispatch job to generate salary transactions for selected payrolls
        // ...

    }

    public function bulkExportPayslips(array $records): void
    {
        // Dispatch export job (PDF) and return download when ready
        // ...

    }
}
