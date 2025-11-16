<?php

namespace App\Filament\Resources\Reports;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\GoodsReceivedNote;
use Filament\Forms\Components\DatePicker;
use App\Filament\Clusters\SupplierCluster;
use App\Filament\Resources\Reports\Pages\ListGoodsReceivedNoteReport;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceivedNoteReportResource extends Resource
{
    protected static ?string $model = GoodsReceivedNote::class; // مجرد placeholder للملاحة
    protected static ?string $slug = 'grn-reports';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;
    protected static ?string $cluster = SupplierCluster::class;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 5;

    public static function getLabel(): ?string
    {
        return __('lang.grn_report');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.grn_report');
    }
    public static function getPluralLabel(): ?string
    {
        return __('lang.grn_report');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceivedNoteReport::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('store_id')
                    ->searchable()
                    ->label(__('lang.store'))
                    ->query(fn(Builder $q, $data) => $q)
                    ->options(Store::active()->get()->pluck('name', 'id')),

                SelectFilter::make('supplier_id')
                    ->searchable()
                    ->label(__('lang.supplier'))
                    ->query(fn(Builder $q, $data) => $q)
                    ->options(Supplier::get()->pluck('name', 'id')),

                SelectFilter::make('product_id')
                    ->label(__('lang.product'))
                    ->multiple()
                    ->searchable()
                    ->options(fn() => Product::where('active', 1)
                        ->get()
                        ->mapWithKeys(fn($p) => [$p->id => "{$p->code} - {$p->name}"])
                        ->toArray())
                    ->getSearchResultsUsing(function (string $search): array {
                        return Product::where('active', 1)
                            ->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%"))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($p) => [$p->id => "{$p->code} - {$p->name}"])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(
                        fn($value): ?string =>
                        optional(Product::find($value))->code . ' - ' . optional(Product::find($value))->name
                    ),

                SelectFilter::make('grn_number')
                    ->searchable()->multiple()
                    ->label(__('lang.grn_number'))
                    ->query(fn(Builder $q, $data) => $q)
                    ->options(
                        GoodsReceivedNote::whereNotNull('grn_number')
                            ->where('grn_number', '!=', '')
                            ->orderBy('grn_number')
                            ->pluck('grn_number', 'grn_number')
                    ),

                Filter::make('show_grn_number')
                    ->toggle()
                    ->label(__('lang.show_grn_number')),

                SelectFilter::make('category_id')
                    ->label(__('lang.category'))
                    ->multiple()
                    ->searchable()
                    ->options(fn() => Category::active()->pluck('name', 'id')->toArray()),

                Filter::make('date')
                    ->schema([
                        DatePicker::make('start')->label(__('lang.start_date')),
                        DatePicker::make('end')->label(__('lang.end_date')),
                    ])

            ], FiltersLayout::AboveContent);
    }

    public static function getNavigationBadge(): ?string
    {
        return 'Report';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'GRN Report';
    }
}
