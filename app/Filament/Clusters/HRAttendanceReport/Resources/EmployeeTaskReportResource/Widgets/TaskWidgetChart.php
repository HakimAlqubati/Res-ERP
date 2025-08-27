<?php

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeTaskReportResource\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\InlineChart\InlineChartWidget;

class TaskWidgetChart extends InlineChartWidget
{
    protected static ?string $heading = 'Chart';

    protected function getData(): array
    {
        $task = Task::find($this->record->id);

        if (!$task) {
            return [];
        }

        $totalSteps = $task->steps()->count();
        $completedSteps = $task->steps()->where('done', 1)->count();
        // $completedSteps = 2;
        $pendingSteps = $totalSteps - $completedSteps;
        // dd($pendingSteps,$completedSteps);
        return [
            'datasets' => [
                [
                    'label' => 'Task Progress',
                    'data' => [$completedSteps, $pendingSteps],
                    'backgroundColor' => ['#00FF00', '#FF0000'],
                    'borderColor' => ['#FF0000', '#FFFF00'],
                ],
            ],
            'labels' => ['Closed', 'Pending'],
        ];
    }
    protected function getType(): string
    {
        return 'pie';
    }

    protected static ?string $maxHeight = '50px';

    protected int | string | array $columnSpan = 'full';



    // public ?string $maxWidth = '!w-[150px]';
}
