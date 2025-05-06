<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources\OrderResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Filament\Widgets\Concerns\CanPoll; 
use Filament\Widgets\ChartWidget;

class TopProductsChart extends ChartWidget
{
    use CanPoll; 

    protected static ?string $heading = 'Top Products Ordered';
    protected static ?int $sort = 1;
    protected static string $color = 'info';
    protected int | string | array $columnSpan = 'full';
    protected function getData(): array
    {
        $response = Http::get(url('/api/branchConsumptionReport/topProducts?limit=10'));

        if (!$response->ok()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $data = collect($response->json('top_products'));

        return [
            'datasets' => [
                [
                    'label' => 'Total Quantity',
                    'data' => $data->pluck('total_quantity')->toArray(),
                ],
            ],
            'labels' => $data->pluck('product_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
