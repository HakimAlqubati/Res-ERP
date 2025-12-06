<?php

namespace App\Filament\Resources\OrderReportsResource;

use Filament\Pages\Enums\SubNavigationPosition;
use Carbon\Carbon;
use stdClass;
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
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
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
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static ?string $cluster = OrderReportsCluster::class;
    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
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
        return $table->deferFilters(false)
            ->filters([
                SelectFilter::make("branch_id")->placeholder('Select')
                    ->label(__('lang.branch'))
                    ->searchable()
                    ->options(Branch::whereIn(
                        'type',
                        [Branch::TYPE_BRANCH, Branch::TYPE_CENTRAL_KITCHEN, Branch::TYPE_POPUP]
                    )
                        ->withPopupsActiveAndExpired()
                        ->active()
                        ->get()->pluck('name', 'id')),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('lang.start_date')),
                        DatePicker::make('end_date')
                            ->label(__('lang.end_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query;
                    })
            ], layout: FiltersLayout::AboveContent)
            // ->query(fn() => self::getReportQuery())
        ;
    }





    public static function processReportData($start_date, $end_date, $branch_id)
    {   // جلب المخزن المرتبط بالفرع
        $storeId = Branch::where('id', $branch_id)->value('store_id');
        $categories = Category::where('active', 1)->notForPos()->pluck('name', 'id');

        $final_result['data'] = [];
        $grand_total_amount = 0.0;   // مجموع remaining_value عبر كل الفئات
        $grand_total_qty    = 0.0;   // مجموع remaining_qty عبر كل الفئات

        $from = Carbon::parse($start_date)->startOfDay();
        $to   = Carbon::parse($end_date)->endOfDay();

        
        foreach ($categories as $cat_id => $cat_name) {
 
            // 3) جلب صفوف المنتجات داخل الفئة بنفس منطق runSourceBalanceByCategorySQL
            $rows = app(GeneralReportProductDetails::class)->runSourceBalanceByCategorySQL(
                (int)$storeId,
                (int)$cat_id,
                $from,
                $to 
            ); 
            // 4) تجميع كميات وقيم الفئة
            $cat_qty   = 0.0; // مجموع remaining_qty (بالوحدة المُدخلة لكل منتج)
            $cat_amount = 0.0; // مجموع remaining_value

            foreach ($rows as $r) {
                $r = (object)$r;
                dd($r);
                // نأخذ فقط السطور ذات الرصيد الإيجابي (نفس ما عملته في التفاصيل)
                $qty = (float) ($r->remaining_qty ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $cat_qty    += $qty;
                $cat_amount += (float) ($r->remaining_value ?? 0);
            }

            // 5) تشكيل عنصر الفئة بنفس structure السابق لديك
            $obj = new stdClass();
            $obj->category_id        = $cat_id;
            $obj->url_report_details = "admin/order-reports/general-report-products/details/$cat_id"
                . "?start_date=$start_date&end_date=$end_date&branch_id=$branch_id&category_id=$cat_id&storeId=$storeId";
            $obj->category           = $cat_name;

            // نفس الحقول والشكل:
            $obj->quantity = formatQunantity($cat_qty);             // الكمية = مجموع remaining_qty
            $obj->price    = formatMoneyWithCurrency($cat_amount);  // الحقل price يعرض المبلغ كما كنت تفعل
            $obj->amount   = formatMoneyWithCurrency($cat_amount);
            $obj->symbol   = getDefaultCurrency();

            $grand_total_qty    += $cat_qty;
            $grand_total_amount += $cat_amount;

            $final_result['data'][] = $obj;
        }  

        $final_result['total_price']    = formatMoneyWithCurrency($grand_total_amount);
        $final_result['total_quantity'] = formatQunantity($grand_total_qty);

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
