<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\GeneralReportOfProductsResource;
use App\Models\Order;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class GeneralReportProductDetails extends Page
{
    protected static string $resource = GeneralReportOfProductsResource::class;

    public string $start_date;
    public string $end_date;
    public $category_id;
    public $branch_id;
    function __construct()
    {
        $this->start_date  = $_GET['start_date'] ?? '';
        $this->end_date = $_GET['end_date'] ?? '';
        $this->category_id = $_GET['category_id'] ?? '';
        $this->branch_id = $_GET['branch_id'] ?? '';
    }
    protected static string $view = 'filament.pages.order-reports.general-report-product-details';
    protected function getViewData(): array
    {
        $report_data['data'] = [];
        $total_price = 0;
        $total_quantity = 0;
        $report_data = $this->getReportDetails($this->start_date, $this->end_date, $this->branch_id, $this->category_id);

        if (isset($report_data['total_price'])) {
            $total_price = $report_data['total_price'];
        }
        if (isset($report_data['total_quantity'])) {
            $total_quantity = $report_data['total_quantity'];
        }
        return [
            'report_data' => $report_data['data'],
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'category' => \App\Models\Category::find($this->category_id)?->name,
            'branch' => \App\Models\Branch::find($this->branch_id)?->name,
            'total_quantity' =>  $total_quantity,
            'total_price' =>  $total_price
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('lang.report_details');
    }



    protected function getLayoutData(): array
    {
        return [
            'breadcrumbs' => $this->getBreadcrumbs(),
            'title' => __('lang.report_details'),
            'maxContentWidth' => $this->getMaxContentWidth(),
        ];
    }


    public function getReportDetails($start_date, $end_date, $branch_id, $category_id)
    {
        $IN  = \App\Models\InventoryTransaction::MOVEMENT_IN  ?? 'in';
        $OUT = \App\Models\InventoryTransaction::MOVEMENT_OUT ?? 'out';
    
        // فرع -> متجر
        $storeId = \App\Models\Branch::where('id', $branch_id)->value('store_id');
        if (! $storeId) {
            return [
                'data' => [],
                'total_price' => getDefaultCurrency() . ' ' . number_format(0, 2),
                'total_quantity' => number_format(0, 2),
            ];
        }
    
        $from = \Carbon\Carbon::parse($start_date)->startOfDay();
        $to   = \Carbon\Carbon::parse($end_date)->endOfDay();
    
        $rows = DB::table('inventory_transactions as it')
            ->join('products as p', 'p.id', '=', 'it.product_id')
            ->leftJoin('branches as b', 'b.store_id', '=', 'it.store_id')
            ->leftJoin('stores as s', 's.id', '=', 'it.store_id')
    
            // (اختياري) لجلب اسم الوحدة المستخدمة في الدخول عبر orders_details
            ->leftJoin('orders as o', function ($j) use ($IN) {
                $j->on('o.id', '=', 'it.transactionable_id')
                  ->where('it.movement_type', '=', $IN);
            })
            ->leftJoin('orders_details as od', function ($j) {
                $j->on('od.order_id', '=', 'o.id')
                  ->on('od.product_id', '=', 'it.product_id');
            })
            ->leftJoin('units as u', 'u.id', '=', 'od.unit_id')
    
            ->whereNull('it.deleted_at')
            ->where('it.store_id', $storeId)
            ->where('p.category_id', $category_id)
            ->whereBetween('it.movement_date', [$from, $to])
    
            ->selectRaw("
                p.id   as product_id,
                p.code as product_code,
                MIN(it.package_size) as package_size,
                IF(JSON_VALID(p.name), REPLACE(JSON_EXTRACT(p.name, '$." . app()->getLocale() . "'), '\"', ''), p.name) as product_name,
    
                MIN(CASE WHEN it.movement_type = ? THEN u.name END) AS unit_name,
    
                -- إجمالي الدخول والخروج والصافي بوحدة القاعدة
                SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS in_qty_base,
                SUM(CASE WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0)) ELSE 0 END) AS out_qty_base,
                SUM(CASE 
                        WHEN it.movement_type = ? THEN (it.quantity * COALESCE(it.package_size, 1.0))
                        WHEN it.movement_type = ? THEN -(it.quantity * COALESCE(it.package_size, 1.0))
                        ELSE 0
                    END
                ) AS net_base,
    
                -- مجموع تكلفة الدخول فقط بالقاعدة (بنفس منطق الدالة المرجعية)
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
            ", [$IN, $IN, $OUT, $IN, $OUT, $IN])
    
            ->groupBy('p.id', 'p.code', 'p.name')
            ->get();
    
        $final = [];
        $totalAmount = 0.0;
        $totalQty = 0.0;
    
        foreach ($rows as $r) {
            $inQtyBase       = (float) $r->in_qty_base;
            $netBase         = (float) $r->net_base;
            $inCostSumBase   = (float) $r->in_cost_sum_base;
    
            $avgInCostPerBase = $inQtyBase > 0 ? ($inCostSumBase / $inQtyBase) : 0.0; // سعر الوحدة (قاعدة)
            $amountBase       = $netBase * $avgInCostPerBase; // قيمة الصافي
    
            $obj = new \stdClass();
            $obj->category_id  = (int) $category_id;
            $obj->product_id   = $r->product_id;
            $obj->product_name = $r->product_name;
            $obj->product_code = $r->product_code;
            $obj->package_size = $r->package_size; // لا نعتمد package_size هنا (الأسعار بالقاعدة)
            $obj->unit_name    = $r->unit_name ?? ''; // اسم وحدة الدخول إن وُجد عبر od/u
            $obj->unit_id      = null;               // إن أردتها، إنضم بوحدة محددة
    
            // الكمية بالصافي (قاعدة)
            $obj->quantity = formatQunantity($netBase);
    
            // السعر (نفس طريقتك: متوسط تكلفة الدخول) × الكمية = amount
            $obj->price  = formatMoney($amountBase, getDefaultCurrency());
            $obj->amount = number_format($amountBase, 2);
            $obj->symbol = getDefaultCurrency();
    
            $totalAmount += $amountBase;
            $totalQty    += $obj->quantity;
    
            $final[] = $obj;
        }
    
        return [
            'data'           => $final,
            'total_price'    => getDefaultCurrency() . ' ' . number_format($totalAmount, 2),
            'total_quantity' => number_format($totalQty, 2),
        ];
    }
    

    public function goBack()
    {
        return back();
    }

    protected function getActions(): array
    {
        return [];
        return  [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')
            ->color('success'),];
    }

    public function exportToPdf()
    {
        $data = $this->getViewData();

        $data = [
            'report_data' => $data['report_data'],
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'category' => \App\Models\Category::find($this->category_id)?->name,
            'branch' => \App\Models\Branch::find($this->branch_id)?->name,
            'total_quantity' =>  $data['total_quantity'],
            'total_price' =>  $data['total_price']
        ];

        $pdf = Pdf::loadView('export.reports.general-report-product-details', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("general-report-product-details" . '.pdf');
            }, "general-report-product-details" . '.pdf');
    }
}
