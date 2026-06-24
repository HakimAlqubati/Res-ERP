<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\EmployeeOvertimeResource\Actions\HeaderActions;

use App\Models\Branch;
use App\Modules\HR\Overtime\OvertimeService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;

class AutoProcess
{
    public static function action()
    {
        return Action::make('auto_process')
            ->label('Auto Process Suggested Overtime')
            ->icon('heroicon-o-bolt')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Process Suggested Overtime')
            ->modalDescription('The system will automatically calculate and store suggested overtime for the selected date range and branch.')
            ->modalSubmitActionLabel('Process Now')
            ->schema([
                Grid::make(2)->schema([
                    DatePicker::make('from_date')
                        ->label('From Date')
                        ->required()
                        ->default(now()),
                    DatePicker::make('to_date')
                        ->label('To Date')
                        ->required()
                        ->default(now()),
                    Select::make('branch_id')
                        ->label('Branch')
                        ->options(Branch::active()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                ]),
            ])
            ->action(function (array $data) {
                try {
                    $service = app(OvertimeService::class);
                    $totalResults = [];

                    $startDate = Carbon::parse($data['from_date']);
                    $endDate = Carbon::parse($data['to_date']);

                    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                        $results = $service->autoProcessSuggestedOvertime($date->format('Y-m-d'), (int) $data['branch_id']);

                        foreach ($results as $branch => $result) {
                            if (is_numeric($result)) {
                                $current = isset($totalResults[$branch]) && is_numeric($totalResults[$branch]) ? $totalResults[$branch] : 0;
                                $totalResults[$branch] = $current + (int) $result;
                            } else {
                                $totalResults[$branch] = $result;
                            }
                        }
                    }

                    $summary = collect($totalResults)->map(function ($result, $branch) {
                        $status = is_numeric($result) ? "{$result} records created" : $result;

                        return "**{$branch}**: {$status}";
                    })->implode("\n");

                    Notification::make()
                        ->title('Overtime Processing Results')
                        ->body($summary ?: 'No records processed.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Processing Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn () => isSuperAdmin() || isSystemManager() || isBranchManager());
    }
}
