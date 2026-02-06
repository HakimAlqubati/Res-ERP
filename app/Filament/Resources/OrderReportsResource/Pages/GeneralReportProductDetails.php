<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Models\Category;
use App\Models\Branch;
use Carbon\Carbon;
use stdClass;
use Filament\Actions\Action;
use App\Filament\Resources\OrderReportsResource\GeneralReportOfProductsResource;
use App\Models\Order;
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
  protected string $view = 'filament.pages.order-reports.general-report-product-details';

  public function runSourceBalanceByCategorySQL(int $categoryId, string $fromDate, string $toDate): array
  {
    $locale = app()->getLocale();
    // اسم المنتج مع دعم JSON locales
    $nameExpr = "IF(JSON_VALID(p.name), JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.\"{$locale}\"')), p.name)";


    $branchId = $this->branch_id;

    $sql = <<<SQL
SELECT
  t.product_id,
  t.product_code,
  t.product_name,
  t.unit_id,
  t.unit_name,
  SUM(t.in_qty)  AS in_qty,
  SUM(t.out_qty) AS out_qty,
  SUM(t.remaining_qty) AS remaining_qty,
  SUM(t.remaining_value) AS remaining_value,
  CASE 
    WHEN SUM(t.remaining_qty) > 0 
    THEN SUM(t.remaining_value) / SUM(t.remaining_qty)
    ELSE 0 
  END AS unit_price
FROM (
  SELECT
    od.product_id,
    p.code AS product_code,
    {$nameExpr} AS product_name,
    od.unit_id,
    u.name AS unit_name,

    od.available_quantity AS in_qty,
    COALESCE(returns_q.returned_qty, 0) AS out_qty,
    GREATEST(od.available_quantity - COALESCE(returns_q.returned_qty, 0), 0) AS remaining_qty,

    GREATEST(od.available_quantity - COALESCE(returns_q.returned_qty, 0), 0) *
    CASE 
      WHEN od.price IS NULL OR od.price = 0 
      THEN COALESCE(up.price, 0)
      ELSE od.price
    END AS remaining_value

  FROM orders_details AS od
  INNER JOIN orders AS o ON o.id = od.order_id
  INNER JOIN products AS p ON p.id = od.product_id
  LEFT JOIN units AS u ON u.id = od.unit_id
  LEFT JOIN unit_prices AS up ON up.product_id = od.product_id AND up.unit_id = od.unit_id

  LEFT JOIN (
      SELECT 
          ro.original_order_id,
          rod.product_id,
          rod.unit_id,
          SUM(rod.quantity) as returned_qty
      FROM returned_orders ro
      JOIN returned_order_details rod ON rod.returned_order_id = ro.id
      WHERE ro.status = 'approved'
        AND ro.deleted_at IS NULL
        AND rod.deleted_at IS NULL
      GROUP BY ro.original_order_id, rod.product_id, rod.unit_id
  ) AS returns_q 
  ON returns_q.original_order_id = o.id 
  AND returns_q.product_id = od.product_id 
  AND returns_q.unit_id = od.unit_id

  WHERE o.deleted_at IS NULL
    AND o.branch_id = :branch_id
    AND p.category_id = :category_id
    AND o.transfer_date BETWEEN :from_date AND :to_date
    AND o.status IN ('ready_for_delivery', 'delevired')

) AS t
GROUP BY
  t.product_id, t.product_code, t.product_name,
  t.unit_id, t.unit_name
ORDER BY
  t.product_id
SQL;


    return DB::select($sql, [
      'branch_id'   => $branchId,
      'category_id' => $categoryId,
      'from_date'   => $fromDate,
      'to_date'     => $toDate,
    ]);
  }


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
      'category' => Category::find($this->category_id)?->name,
      'branch' => Branch::find($this->branch_id)?->name,
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

    // فرع -> متجر
    $storeId = Branch::where('id', $branch_id)->value('store_id');

    if (! $storeId) {
      return [
        'data' => [],
        'total_price' => getDefaultCurrency() . ' ' . number_format(0, 2),
        'total_quantity' => number_format(0, 2),
      ];
    }

    $this->storeId = $storeId;
    $from = Carbon::parse($start_date)->startOfDay();
    $to   = Carbon::parse($end_date)->endOfDay();
    $rows = $this->runSourceBalanceByCategorySQL($category_id, $from, $to);



    $final = [];
    $totalAmount = 0.0;
    $totalQty = 0.0;

    foreach ($rows as $r) {
      $r = (object)$r;
      // dd($r,gettype($r));
      // $inQtyBase       = (float) $r->remaining_qty;
      $netBase         = (float) $r->remaining_qty;
      if ($netBase <= 0) {
        continue;
      }
      // $inCostSumBase   = (float) $r->in_cost_sum_base;

      // $avgInCostPerBase = $inQtyBase > 0 ? ($inCostSumBase / $inQtyBase) : 0.0; // سعر الوحدة (قاعدة)
      // $amountBase       = $netBase * $avgInCostPerBase; // قيمة الصافي

      $amountBase = $r->remaining_value;
      $obj = new stdClass();
      $obj->category_id  = (int) $category_id;
      $obj->product_id   = $r->product_id;
      $obj->product_name = $r->product_name;
      $obj->product_code = $r->product_code;
      $obj->unit_name    = $r->unit_name ?? '';
      $obj->unit_id      = $r->unit_id ?? '';

      // الكمية بالصافي (قاعدة)
      $obj->quantity =  formatQunantity($netBase);

      // السعر (نفس طريقتك: متوسط تكلفة الدخول) × الكمية = amount
      $obj->price  = formatMoney($amountBase, getDefaultCurrency());
      $obj->amount = number_format($amountBase, 2);
      $obj->symbol = getDefaultCurrency();

      $obj->unit_price = formatMoneyWithCurrency($r->unit_price);
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
      'category' => Category::find($this->category_id)?->name,
      'branch' => Branch::find($this->branch_id)?->name,
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
