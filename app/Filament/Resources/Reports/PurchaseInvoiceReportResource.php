<?php

namespace App\Filament\Resources\Reports;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Models\Category;
use Filament\Forms\Components\DatePicker;
use App\Filament\Clusters\InventoryReportsCluster;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Clusters\SupplierStoresReportsCluster;
use App\Filament\Resources\PurchaseInvoiceReportResource\Reports\Pages\ListPurchaseInvoiceReport;
use App\Models\FakeModelReports\PurchaseInvoiceReport;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Store;
use App\Models\Supplier;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseInvoiceReportResource extends Resource
{
    protected static ?string $model = PurchaseInvoiceReport::class;
    protected static ?string $slug = 'purchase-invoice-reports';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = SupplierCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 4;

    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.purchase_invoice_report');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.purchase_invoice_report');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.purchase_invoice_report');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseInvoiceReport::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make("store_id")
                    ->searchable()
                    ->label(__('lang.store'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Store::active()->get()->pluck('name', 'id')),

                SelectFilter::make("supplier_id")
                    ->searchable()
                    ->label(__('lang.supplier'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(Supplier::get()->pluck('name', 'id')),
                SelectFilter::make("product_id")
                    ->label(__('lang.product'))
                    ->multiple()
                    ->searchable()
                    ->options(fn() => Product::where('active', 1)
                        ->get()
                        ->mapWithKeys(fn($product) => [
                            $product->id => "{$product->code} - {$product->name}"
                        ])
                        ->toArray())
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
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
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        optional(Product::find($value))->code . ' - ' . optional(Product::find($value))->name
                    ),
                SelectFilter::make("invoice_no")
                    ->searchable()->multiple()
                    ->label(__('lang.invoice_no'))
                    ->query(function (Builder $q, $data) {
                        return $q;
                    })->options(
                        PurchaseInvoice::whereNotNull('invoice_no')
                            ->where('invoice_no', '!=', '')
                            ->orderBy('invoice_no')
                            ->pluck('invoice_no', 'invoice_no')
                    ),
                Filter::make('show_invoice_no')
                    ->toggle()
                    ->label(__('lang.show_invoice_no')),
                // Toggle::make('show_invoice_no')
                //     ->label(__('lang.show_invoice_no'))
                //     ->default(false)
                //     ->onColor('success')
                //     ->offColor('danger')
                //     ->inline(),
                SelectFilter::make("category_id")
                    ->label(__('lang.category'))
                    ->multiple()
                    ->searchable()
                    ->options(function () {
                        return Category::active()->pluck('name', 'id')->toArray();
                    }),


                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('lang.start_date')),
                        DatePicker::make('to')
                            ->label(__('lang.end_date')),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['from'])) {
                            $query->whereDate('date', '>=', $data['from']);
                        }
                        if (!empty($data['to'])) {
                            $query->whereDate('date', '<=', $data['to']);
                        }
                    }),

            ], FiltersLayout::AboveContent);
    }
    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }
    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Purchase Report';
    }
}
