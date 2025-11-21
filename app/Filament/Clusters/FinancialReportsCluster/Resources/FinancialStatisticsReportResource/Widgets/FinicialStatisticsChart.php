<?php

namespace App\Filament\Clusters\FinancialReportsCluster\Resources\FinancialStatisticsReportResource\Widgets;

use Filament\Widgets\ChartWidget;

class FinicialStatisticsChart extends ChartWidget
{
    protected ?string $heading = 'Finicial Statistics Chart';
    protected ?string $maxHeight = '10';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => [10, 25, 30, 15, 40, 20, 35],
                ],
            ],
            'labels' => [
                'Sat',
                'Sun',
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
