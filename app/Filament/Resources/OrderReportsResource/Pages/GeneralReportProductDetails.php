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
    public $storeId;
    function __construct()
    {
        $this->start_date  = $_GET['start_date'] ?? '';
        $this->end_date = $_GET['end_date'] ?? '';
        $this->category_id = $_GET['category_id'] ?? '';
        $this->branch_id = $_GET['branch_id'] ?? '';
        $this->storeId = $_GET['storeId'] ?? '';
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

        $this->storeId = $storeId;
        $from = \Carbon\Carbon::parse($start_date)->startOfDay();
        $to   = \Carbon\Carbon::parse($end_date)->endOfDay();

        $rows = DB::table('inventory_transactions as it')
            ->join('products as p', 'p.id', '=', 'it.product_id')
            ->leftJoin('branches as b', 'b.store_id', '=', 'it.store_id')
            ->leftJoin('stores as s', 's.id', '=', 'it.store_id')
            // الدخول فقط من الطلبات
            ->leftJoin('orders as o', function ($j) use ($IN) {
                $j->on('o.id', '=', 'it.transactionable_id')
                    ->where('it.movement_type', '=', $IN);
            })
            ->leftJoin('orders_details as od', function ($j) {
                $j->on('od.order_id', '=', 'o.id')
                    ->on('od.product_id', '=', 'it.product_id');
            })
            ->leftJoin('unit_prices as up', function ($j) use ($IN) {
                $j->on('up.product_id', '=', 'p.id')
                    ->on('up.unit_id', '=', 'it.unit_id')
                    // ->where('it.movement_type', '=', $IN)
                    ;
            })
            // الوحدة مباشرة من الحركة
            ->leftJoin('units as u', 'u.id', '=', 'it.unit_id')
            ->whereNull('it.deleted_at')
            ->where('it.store_id', $storeId)
            ->where('p.category_id', $category_id)
            ->when($start_date && $end_date, function ($query) use ($from, $to) {
                return $query->whereBetween('it.movement_date', [$from, $to]);
            })
            ->selectRaw("
        p.id   as product_id,
        p.code as product_code,
        IF(JSON_VALID(p.name), REPLACE(JSON_EXTRACT(p.name, '$." . app()->getLocale() . "'), '\"', ''), p.name) as product_name,

        it.id as tx_id,
        it.movement_type,
        it.movement_date,
        it.quantity,
        COALESCE(it.package_size, 1.0) as package_size,

        it.unit_id,
        u.name as unit_name,

        -- سعر الوحدة كما سُجل في الحركة (بنفس unit_id للحركة)
         CASE 
        WHEN it.price IS NULL OR it.price = 0 
        THEN up.price 
        ELSE it.price 
        END as unit_price,

        it.notes
    ")
            ->orderBy('p.id')
            ->orderBy('it.movement_date')
            ->orderBy('it.id')
            ->get();

            // dd($rows);
        // print_html_table($rows, [
        //     'column' => 'movement_type',
        //     'value'  => 'in',
        //     'color'  => '#ECFDF5',   // خلفية
        //     'text'   => '#065F46',   // (اختياري) لون النص
        // ]);

    
        $result = $this->fifoRemainingPerInUnit($rows);

        $items = collect($result['items'])
            ->sortBy(['product_code', 'movement_date', 'source_tx_id'])
            ->values()
            ->all();

        // print_html_table($items);
        $final = [];
        $totalAmount = 0.0;
        $totalQty = 0.0;

        foreach ($items as $r) {
            $r= (object)$r;
            // dd($r,gettype($r));
            // $inQtyBase       = (float) $r->remaining_qty;
            $netBase         = (float) $r->remaining_qty;
            // $inCostSumBase   = (float) $r->in_cost_sum_base;

            // $avgInCostPerBase = $inQtyBase > 0 ? ($inCostSumBase / $inQtyBase) : 0.0; // سعر الوحدة (قاعدة)
            // $amountBase       = $netBase * $avgInCostPerBase; // قيمة الصافي

            $amountBase = $r->remaining_value;
            $obj = new \stdClass();
            $obj->category_id  = (int) $category_id;
            $obj->product_id   = $r->product_id;
            $obj->product_name = $r->product_name;
            $obj->product_code = $r->product_code;
            $obj->package_size = $r->package_size; // لا نعتمد package_size هنا (الأسعار بالقاعدة)
            $obj->unit_name    = $r->unit_name ?? ''; // اسم وحدة الدخول إن وُجد عبر od/u
            $obj->unit_id      = $r->unit_id ?? '';               // إن أردتها، إنضم بوحدة محددة

            // الكمية بالصافي (قاعدة)
            $obj->quantity =  formatQunantity($netBase);

            // السعر (نفس طريقتك: متوسط تكلفة الدخول) × الكمية = amount
            $obj->price  = formatMoney($amountBase, getDefaultCurrency());
            $obj->amount = number_format($amountBase, 2);
            $obj->symbol = getDefaultCurrency();

            $totalAmount += $amountBase;
            $totalQty    += $netBase ?? 0;

            $final[] = $obj;
        }

        return [
            'data'           => $final,
            'total_price'    => getDefaultCurrency() . ' ' . number_format($totalAmount, 2),
            'total_quantity' => formatQunantity($totalQty),
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

    /**
     * يحسب المتبقي لكل منتج بحسب طبقات الدخول (وحدة الدخول وسعرها الأصلي)
     * بدون متوسط: يبقى سعر الطبقة كما سُجل عند الدخول.
     *
     * @param \Illuminate\Support\Collection $rows صفوف الحركات من الاستعلام أعلاه
     * @return array نتائج مجمّعة per product & per in-layer
     */
    public function fifoRemainingPerInUnit($rows)
    {
        // layers[product_id] = queue of layers
        // كل layer: [
        //   'product_id','product_code','product_name',
        //   'unit_id','unit_name','package_size',
        //   'unit_price',        // سعر الوحدة (بنفس unit_id)
        //   'base_qty',          // كمية الطبقة بالـ base unit = quantity * package_size
        //   'source_tx_id', 'movement_date', 'notes'
        // ]
        $layers = [];
        $negatives = []; // لتتبع أي سحوبات تتجاوز الرصيد (اختياري)

        foreach ($rows as $r) {
            $pid = $r->product_id;
            $baseQty = (float)$r->quantity * (float)$r->package_size;

            if (!isset($layers[$pid])) {
                $layers[$pid] = [];
            }

            if ($r->movement_type === 'in') {
                // أنشئ طبقة دخول بسعرها ووحدتها
                $layers[$pid][] = [
                    'product_id'   => $r->product_id,
                    'product_code' => $r->product_code,
                    'product_name' => $r->product_name,

                    'unit_id'      => $r->unit_id,
                    'unit_name'    => $r->unit_name,
                    'package_size' => (float)$r->package_size,

                    'unit_price'   => (float)$r->unit_price, // سعر الوحدة كما سُجل

                    'base_qty'     => $baseQty,              // مخزون الطبقة بالأساس
                    'source_tx_id' => $r->tx_id,
                    'movement_date' => $r->movement_date,
                    'notes'        => $r->notes,
                ];
            } elseif ($r->movement_type === 'out') {
                // خصم FIFO عبر الطبقات القائمة
                $toConsume = $baseQty;

                // استهلك من الطبقات الأقدم فالأحدث
                for ($i = 0; $i < count($layers[$pid]) && $toConsume > 0; $i++) {
                    $available = $layers[$pid][$i]['base_qty'];
                    if ($available <= 0) {
                        continue;
                    }
                    $consume = min($available, $toConsume);
                    $layers[$pid][$i]['base_qty'] -= $consume;
                    $toConsume -= $consume;
                }

                // إذا بقي سحب بلا رصيد، سجّله (اختياري للتنبيه)
                if ($toConsume > 0.0000001) {
                    $negatives[] = [
                        'product_id' => $pid,
                        'tx_id'      => $r->tx_id,
                        'missing_base_qty' => $toConsume,
                    ];
                }
            }
        }

        // إبني النتائج النهائية: المتبقي لكل طبقة دخول (بالوحدة الأصلية للطبقة) وقيمتها بنفس السعر الأصلي
        $results = [];

        foreach ($layers as $pid => $productLayers) {
            foreach ($productLayers as $ly) {
                if ($ly['base_qty'] <= 0) {
                    continue;
                }

                // حوّل المتبقي من base إلى وحدة الطبقة:
                // remaining_qty_in_layer_unit = base_qty / package_size
                $remaining_qty = $ly['package_size'] > 0 ? ($ly['base_qty'] / $ly['package_size']) : 0;

                // سعر الوحدة يبقى كما دخلت الطبقة:
                $unit_price = $ly['unit_price'];

                // القيمة الإجمالية للمتبقي في هذه الطبقة:
                // بما أن السعر للوحدة (unit_id للطبقة)، فقيمة المتبقي = remaining_qty * unit_price
                $remaining_value = $remaining_qty * $unit_price;

                $results[] = [
                    'product_id'   => $ly['product_id'],
                    'product_code' => $ly['product_code'],
                    'product_name' => $ly['product_name'],

                    'unit_id'      => $ly['unit_id'],
                    'unit_name'    => $ly['unit_name'],
                    'package_size' => $ly['package_size'],

                    'remaining_qty' => round($remaining_qty, 4),
                    'unit_price'   => round($unit_price, 4),
                    'remaining_value' => round($remaining_value, 4),

                    'source_tx_id' => $ly['source_tx_id'],
                    'movement_date' => $ly['movement_date'],
                    'notes'        => $ly['notes'],
                ];
            }
        }

        // يمكن إرجاع negatives أيضًا لو أردت التنبيه
        return [
            'items'     => $results,
            'negatives' => $negatives,
        ];
    }
}
