<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Models\Category;
use App\Models\Branch;
use App\Models\InventoryTransaction;
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

  public function runSourceBalanceByCategorySQL(int $storeId, int $categoryId, string $fromDate, string $toDate): array
  {
    $locale = app()->getLocale();
    // اسم المنتج مع دعم JSON locales
    $nameExpr = "IF(JSON_VALID(p.name), JSON_UNQUOTE(JSON_EXTRACT(p.name, '$.\"{$locale}\"')), p.name)";
    $sql = <<<SQL
SELECT
  t.product_id,
  t.product_code,
  t.product_name,
  t.unit_id,
  t.unit_name,
  t.package_size,
  t.unit_price,
  SUM(t.in_qty_base)  AS in_qty_base,
  SUM(t.out_qty_base) AS out_qty_base,
  SUM(t.remaining_qty_unit) AS remaining_qty,
  SUM(t.remaining_value)    AS remaining_value
FROM (
  SELECT
    it_in.id AS in_tx_id,
    it_in.product_id,
    p.code AS product_code,
    {$nameExpr} AS product_name,
    it_in.movement_date,
    it_in.unit_id,
    u.name AS unit_name,
    COALESCE(it_in.package_size, 1.0) AS package_size,

    it_in.quantity AS in_qty_unit,
    it_in.quantity * COALESCE(it_in.package_size, 1.0) AS in_qty_base,

    COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0) AS out_qty_base,

    GREATEST(
      it_in.quantity * COALESCE(it_in.package_size, 1.0)
      - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0),
      0
    ) AS remaining_base,

    GREATEST(
      it_in.quantity * COALESCE(it_in.package_size, 1.0)
      - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0),
      0
    ) / COALESCE(it_in.package_size, 1.0) AS remaining_qty_unit,

    CASE 
      WHEN it_in.price IS NULL OR it_in.price = 0 
      THEN COALESCE(up.price, 0)
      ELSE it_in.price
    END AS unit_price,
    -- COALESCE(it_in.price, 0) AS unit_price,

    (
      GREATEST(
        it_in.quantity * COALESCE(it_in.package_size, 1.0)
        - COALESCE(SUM(it_out.quantity * COALESCE(it_out.package_size, 1.0)), 0),
        0
      ) / COALESCE(it_in.package_size, 1.0)
    ) *
    CASE 
      WHEN it_in.price IS NULL OR it_in.price = 0 
      THEN COALESCE(up.price, 0)
      ELSE it_in.price
    END AS remaining_value
    -- COALESCE(it_in.price, 0) AS remaining_value

  FROM inventory_transactions AS it_in
  LEFT JOIN inventory_transactions AS it_out
    ON it_out.source_transaction_id = it_in.id
   AND it_out.movement_type = 'out'
   AND it_out.store_id = it_in.store_id
   AND it_out.deleted_at IS  NULL
   and it_out.transactionable_type = :returned_orders

  LEFT JOIN units AS u
    ON u.id = it_in.unit_id

  LEFT JOIN unit_prices AS up
    ON up.product_id = it_in.product_id
   AND up.unit_id    = it_in.unit_id

  INNER JOIN products AS p
    ON p.id = it_in.product_id
   AND p.category_id = :category_id

  WHERE it_in.deleted_at IS NULL
    AND it_in.movement_type = 'in'
    AND it_in.store_id = :store_id
    AND it_in.movement_date BETWEEN :from_date AND :to_date
    AND it_in.transactionable_type = :order_morph

  GROUP BY
    it_in.id, it_in.product_id, p.code, p.name,
    it_in.unit_id, u.name,
    it_in.package_size, it_in.quantity, it_in.price
    , up.price
    , it_in.movement_date
) AS t
GROUP BY
  t.product_id, t.product_code, t.product_name,
  t.unit_id, t.unit_name, t.package_size, t.unit_price
ORDER BY
--   t.product_code, t.unit_id, t.package_size
t.product_id
SQL;

    return DB::select($sql, [
      'store_id'    => $storeId,
      'category_id' => $categoryId,
      'from_date'   => $fromDate,
      'to_date'     => $toDate,
      'order_morph'  => 'App\\Models\\Order',
      'returned_orders' => 'App\\Models\\ReturnedOrder'
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
    $IN  = InventoryTransaction::MOVEMENT_IN  ?? 'in';
    $OUT = InventoryTransaction::MOVEMENT_OUT ?? 'out';

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
    $rows = $this->runSourceBalanceByCategorySQL($storeId, $category_id, $from, $to);

    // dd($rows);
    // print_html_table($rows, [
    //     'column' => 'movement_type',
    //     'value'  => 'in',
    //     'color'  => '#ECFDF5',   // خلفية
    //     'text'   => '#065F46',   // (اختياري) لون النص
    // ]);





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
      $obj->package_size = $r->package_size; // لا نعتمد package_size هنا (الأسعار بالقاعدة)
      $obj->unit_name    = $r->unit_name ?? ''; // اسم وحدة الدخول إن وُجد عبر od/u
      $obj->unit_id      = $r->unit_id ?? '';               // إن أردتها، إنضم بوحدة محددة

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
