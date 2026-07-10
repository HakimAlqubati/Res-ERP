<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\OrderReportsCluster;
use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FakeModelReports\ReportProductQuantities;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportProductQuantitiesResource extends Resource
{
    protected static ?string $model = ReportProductQuantities::class;

    protected static ?string $slug = 'report-product-quantities';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = OrderReportsCluster::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

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
                SelectFilter::make('product_id')
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
                            ->mapWithKeys(fn ($product) => [
                                $product->id => "{$product->code} - {$product->name}",
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->code.' - '.Product::find($value)?->name)
                    ->options(function () {
                        return Product::where('active', 1)
                            ->get()
                            ->mapWithKeys(fn ($product) => [
                                $product->id => "{$product->code} - {$product->name}",
                            ]);
                    }),
                SelectFilter::make('category_id')
                    ->label(__('lang.category'))
                    ->multiple()
                    ->searchable()
                    ->options(Category::active()->notForPos()->pluck('name', 'id')),
                SelectFilter::make('branch_id')
                    ->label('Branch')->searchable()->multiple()
                    ->options(Branch::whereIn('type', [
                        Branch::TYPE_BRANCH,
                        Branch::TYPE_CENTRAL_KITCHEN,
                        Branch::TYPE_POPUP,
                    ])
                        ->activePopups()
                        ->active()->pluck('name', 'id')),

                Filter::make('order_number')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('order_number')
                            ->label(__('Order Number'))
                            ->numeric(),
                    ]),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('start_date')
                            ->default(now()->startOfMonth()->toDateString())
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')
                            ->default(now()->endOfMonth()->toDateString())
                            ->label(__('lang.end_date')),
                    ]),
            ], layout: FiltersLayout::AboveContent);
    }

    public static function canViewAny(): bool
    {
        if (isSuperAdmin() || isSystemManager() || isBranchManager() || isStoreManager() || isSuperVisor()) {
            return true;
        }

        return false;
    }
}
