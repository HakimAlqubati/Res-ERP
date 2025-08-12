<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\OrderCluster;
use App\Filament\Clusters\OrderReportsCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails;
use App\Filament\Resources\OrderReportsResource\Pages\ListGeneralReportOfProducts;
use App\Models\Branch;
use App\Models\Category;
use App\Models\FakeModelReports\GeneralReportOfProducts;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderDetails;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GeneralReportOfProductsResource extends Resource
{
    protected static ?string $model = GeneralReportOfProducts::class;
    protected static ?string $slug = 'general-report-products';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = OrderReportsCluster::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 1;
    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.general_report_of_products');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }


    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make("branch_id")->placeholder('Select')
                    ->label(__('lang.branch'))
                    ->options(Branch::whereIn('type', [Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN, Branch::TYPE_POPUP])->active()
                        ->get()->pluck('name', 'id')),
                Filter::make('date')
                    ->form([
                        DatePicker::make('start_date')
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')
                            ->label(__('lang.end_date')),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query;
                    })
            ], layout: FiltersLayout::AboveContent)
            // ->query(fn() => self::getReportQuery())
        ;
    }





    public static function processReportData($start_date, $end_date, $branch_id)
    {
        $IN  = InventoryTransaction::MOVEMENT_IN  ?? 'in';
        $OUT = InventoryTransaction::MOVEMENT_OUT ?? 'out';

        // جلب المخزن المرتبط بالفرع
        $storeId = Branch::where('id', $branch_id)->value('store_id');

        $from = \Carbon\Carbon::parse($start_date)->startOfDay();
        $to   = \Carbon\Carbon::parse($end_date)->endOfDay();

        // تجميع على مستوى الفئة مع حسابات التكلفة كالدالة المرجعية
        $rows = DB::table('inventory_transactions as it')
            ->join('products as p', 'p.id', '=', 'it.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('stores as s', 's.id', '=', 'it.store_id')
            ->leftJoin('branches as b', 'b.store_id', '=', 's.id')
            ->whereNull('it.deleted_at')
            ->when($storeId, fn($q) => $q->where('it.store_id', $storeId))
            // NOTE: استخدم نفس حقل التاريخ الذي تعتمد عليه في الدالة المرجعية
            ->when($start_date && $end_date, fn($q) => $q->whereBetween('it.movement_date', [$from, $to]))
            ->selectRaw("
                p.category_id,
    
                -- إجمالي الدخول والخروج بوحدة القاعدة
                SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS in_qty_base,
                SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS out_qty_base,
    
                -- الصافي بوحدة القاعدة
                SUM(
                    CASE
                        WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0))
                        WHEN it.movement_type = ? THEN -(it.quantity * COALESCE(it.package_size, 1.0))
                        ELSE 0
                    END
                ) AS net_base,
    
                -- مجموع تكلفة الدخول فقط بوحدة القاعدة (بنفس منطق الدالة المرجعية)
                SUM(
                    CASE
                        WHEN it.movement_type = ?
                        THEN (
                            ( NULLIF(it.price, 0) / NULLIF(COALESCE(it.package_size, 1.0), 0) )
                            * (it.quantity * COALESCE(it.package_size, 1.0))
                        )
                        ELSE 0
                    END
                ) AS in_cost_sum_base
            ", [$IN, $OUT, $IN, $OUT, $IN])
            ->groupBy('p.category_id')
            ->get();

        // تحويل النتائج إلى نفس بنية إخراجك السابقة (price/amount لكل فئة)
        $data = [];
        foreach ($rows as $r) {
            $inQtyBase       = (float) $r->in_qty_base;
            $outQtyBase      = (float) $r->out_qty_base;
            $netBase         = (float) $r->net_base;
            $inCostSumBase   = (float) $r->in_cost_sum_base;

            $avgInCostPerBase = $inQtyBase > 0 ? ($inCostSumBase / $inQtyBase) : 0.0; // سعر الوحدة (قاعدة)
            $amountBase       = $netBase * $avgInCostPerBase; // قيمة الصافي بتكلفة متوسط الدخول

            if (! isset($data[$r->category_id])) {
                $data[$r->category_id] = [
                    'available_quantity' => 0.0, // سنعرضها كـ quantity
                    'price'              => 0.0, // سنخزن المبلغ (amount) أولًا ثم ننسّقه
                    'unit_price'         => 0.0, // سعر الوحدة (اختياري لو تحب تعرضه)
                ];
            }

            $data[$r->category_id]['available_quantity'] += $netBase;
            $data[$r->category_id]['price']              += $amountBase;     // سنعرضه كـ amount لاحقًا
            // ممكن تحفظ متوسط سعر الوحدة على مستوى الفئة (مرجّحًا بكمية الصافي أو الدخول)
            // هنا لن نجمّعه لأنه قد يختلف بين المنتجات؛ نحتفظ بآخر قيمة أو اتركه إذا لا تحتاج عرضه.
            $data[$r->category_id]['unit_price']          = $avgInCostPerBase;
        }

        // جلب الفئات النشطة
        $categories = Category::where('active', 1)->pluck('name', 'id');

        $final_result['data'] = [];
        $total_price = 0;     // إجمالي المبالغ (amount)
        $total_quantity = 0;  // إجمالي الصافي

        foreach ($categories as $cat_id => $cat_name) {
            $netQty   = (float) ($data[$cat_id]['available_quantity'] ?? 0.0);
            $amount   = (float) ($data[$cat_id]['price'] ?? 0.0);             // مجموع قيمة الصافي بتكلفة متوسط الدخول
            $unitPrice = (float) ($data[$cat_id]['unit_price'] ?? 0.0);        // معلوماتي فقط

            $obj = new \stdClass();
            $obj->category_id        = $cat_id;
            $obj->url_report_details = "admin/order-reports/general-report-products/details/$cat_id?start_date=$start_date&end_date=$end_date&branch_id=$branch_id&category_id=$cat_id";
            $obj->category           = $cat_name;

            // الكمية (الصافي) — بإمكانك تقريبها كما كان:
            $obj->quantity = round($netQty, 0);

            // price هنا سنعرض "قيمة الفئة" بصيغة عملة (كما كنت تعرض سابقًا)
            // إن أردت عرض "سعر الوحدة" بدلًا منها ضع formatMoney($unitPrice, getDefaultCurrency())
            $obj->price  = formatMoney($amount, getDefaultCurrency()); // للحفاظ على شكل الحقل السابق
            $obj->amount = number_format($amount, 2);
            $obj->symbol = getDefaultCurrency();

            $total_price    += $amount;
            $total_quantity += $obj->quantity;

            $final_result['data'][] = $obj;
        }

        $final_result['total_price']    = getDefaultCurrency() . ' ' . number_format($total_price, 2);
        $final_result['total_quantity'] = number_format($total_quantity, 2);

        return $final_result;
    }




    public static function getPages(): array
    {
        return [
            'index' => ListGeneralReportOfProducts::route('/'),
            'details' => GeneralReportProductDetails::route('/details/{category_id}'),
        ];
    }
}
