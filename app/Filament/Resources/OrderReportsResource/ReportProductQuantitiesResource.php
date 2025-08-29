<?php

namespace App\Filament\Resources\OrderReportsResource;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderReportsCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;
use App\Models\Branch;
use App\Models\FakeModelReports\ReportProductQuantities;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportProductQuantitiesResource extends Resource
{
    protected static ?string $model = ReportProductQuantities::class;
    protected static ?string $slug = 'report-product-quantities';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = OrderReportsCluster::class;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;

    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportProductQuantities::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table->deferFilters(false)
            ->filters([ 
                SelectFilter::make("product_id")
                    // ->multiple()
                    ->label(__('lang.product'))->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::query()
                            ->where(function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn($value): ?string => Product::find($value)?->code . ' - ' . Product::find($value)?->name)
                    ->options(function () {
                        return Product::where('active', 1)
                            ->get()
                            ->mapWithKeys(fn($product) => [
                                $product->id => "{$product->code} - {$product->name}"
                            ]);
                    }),
                SelectFilter::make('branch_id')
                    ->label('Branch')->searchable()
                    ->options(Branch::whereIn('type', [
                        Branch::TYPE_BRANCH,
                        Branch::TYPE_CENTRAL_KITCHEN,
                        Branch::TYPE_POPUP
                    ])
                        ->activePopups()
                        ->active()->pluck('name', 'id')),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')
                            ->label(__('lang.end_date')),
                    ]) 
            ], layout: FiltersLayout::AboveContent);
    }
 
}
