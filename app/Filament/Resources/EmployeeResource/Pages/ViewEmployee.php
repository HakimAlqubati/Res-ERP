<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
    return $data;
  }
  protected function getHeaderActions(): array
  {
    return [
      EditAction::make(),
      DeleteAction::make()
        ->visible(
          fn() => EmployeeResource::canDeleteAny()
            && EmployeeResource::canDelete($this->record)
        ),
      \App\Filament\Resources\EmployeeResource\EmployeeActions::changeBranch(),

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
      \Filament\Actions\Action::make('attendance_report')
        ->label(__('lang.attendance_report'))
        ->color('info')
        ->icon('heroicon-o-chart-bar')
        ->url(fn($record) => \App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeAttednaceReportResource::getUrl('index', [
          'tableFilters[employee_id]' => $record->id,
          'tableFilters[date_range][start_date]' => now()->startOfMonth()->toDateString(),
          'tableFilters[date_range][end_date]' => now()->endOfMonth()->toDateString(),
        ]))
        ->openUrlInNewTab(),

    ];
  }
}
