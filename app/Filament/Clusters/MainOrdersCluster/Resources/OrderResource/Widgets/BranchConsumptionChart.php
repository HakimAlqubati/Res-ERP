<?php

namespace App\Filament\Clusters\MainOrdersCluster\Resources\OrderResource\Widgets;

use Filament\Schemas\Schema;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use App\Services\Orders\Reports\OrdersReportsService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

use Illuminate\Support\Carbon;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;



class BranchConsumptionChart extends ChartWidget implements HasForms
{
    use HasFiltersForm,
        InteractsWithForms;

    protected string $view = 'vendor.filament.widgets.branch-consumption-chart';
    protected ?string $heading = 'Chart';
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';


    public array $branchIds = [];
    public array $productIds = [];
    public array $categoryIds = [];
    public ?string $fromDate = null;
    public ?string $toDate = null;

    public function filtersForm(Schema $schema): Schema

    {
        return $schema
            ->components([
                DatePicker::make('fromDate')
                    ->label('From Date')
                    ->reactive()
                    ->default(now()->subDays(7)->toDateString()),

                DatePicker::make('toDate')
                    ->label('To Date')
                    ->reactive()
                    ->default(now()->toDateString()),

                Select::make('branchIds')->multiple()
                    ->label('Branch')
                    ->options(Branch::pluck('name', 'id'))
                    ->searchable()
                    ->reactive()
                    ->placeholder('Choose a Branch'),

                Select::make('productIds')
                    ->label('Products')
                    ->options(Product::pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->reactive()
                    ->placeholder('All Products'),

                Select::make('categoryIds')
                    ->label('Categories')
                    ->options(Category::pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->reactive()
                    ->placeholder('All Categories'),
            ]);
    }

    public function getBranchOptions(): array
    {
        return Branch::pluck('name', 'id')->toArray();
    }

    public function getProductOptions(): array
    {
        return Product::pluck('name', 'id')->toArray();
    }

    public function getCategoryOptions(): array
    {
        return Category::pluck('name', 'id')->toArray();
    }


    protected function getData(): array
    {
        $from = $this->fromDate ?? now()->subDays(7)->format('Y-m-d');
        $to = $this->toDate ?? now()->format('Y-m-d');
        
        // dd($branch, $this->branchIds, $this->productIds, $this->categoryIds);
        $data = OrdersReportsService::getBranchConsumption(
            $from,
            $to,
            
            $this->branchIds ?: null,
            $this->productIds ?: null,
            $this->categoryIds ?: null
        );

        $labels = [];
        $datasets = [];

        // افتراضًا نأخذ أول فرع فقط (يمكنك التوسيع لاحقاً)
        $branch = $data[0] ?? null;
        if (!$branch) return ['datasets' => [], 'labels' => []];

        foreach ($branch['products'] as $product) {
            $productLabel = $product['product_name'];
            $daily = collect($product['daily'])->keyBy('date');

            // تجهيز المحور الأفقي
            foreach (range(0, 6) as $dayOffset) {
                $date = Carbon::now()->subDays(6 - $dayOffset)->format('Y-m-d');
                if (!in_array($date, $labels)) {
                    $labels[] = $date;
                }
            }

            $quantityData = [];
            foreach ($labels as $label) {
                $quantityData[] = $daily[$label]['total_quantity'] ?? 0;
            }

            $datasets[] = [
                'label' => $productLabel,
                'data' => $quantityData,
                'fill' => false,
                'tension' => 0.3,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function getFilters(): ?array
    {
        return Branch::active()->pluck('name', 'id')->toArray();
    }

    protected function getType(): string
    {
        return 'bar';
    }
    // public function updateChartData(): void
    // {
    //     $this->dispatch('updateChartData', data: $this->getCachedData());
    // }
    public function updateChartData(): void
    {
        // Clear cache so getCachedData() uses fresh data
        $this->cachedData = null;

        // Refresh checksum to force update
        $this->dataChecksum = $this->generateDataChecksum();

        // Send updated data to chart
        $this->dispatch('updateChartData', data: $this->getCachedData());
    }
}
