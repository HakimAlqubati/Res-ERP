<?php

namespace App\Filament\Resources\Reports\Pages;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\Action;
use App\Filament\Resources\Reports\StoresReportResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;

use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ListStoresReport extends ListRecords
{
    protected static string $resource = StoresReportResource::class;
    protected string $view = 'filament.pages.stock-report.stores-report';


    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make("store_id")
                ->label(__('lang.store'))

                ->default(getDefaultStore())
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Store::get()->pluck('name', 'id')),

            SelectFilter::make("supplier_id")
                ->label(__('lang.supplier'))
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Supplier::get()->pluck('name', 'id')),

            // SelectFilter::make("product_id")
            //     ->label(__('lang.product'))
            //     ->searchable()
            //     ->multiple()
            //     ->query(function (Builder $q, $data) {
            //         return $q;
            //     })->options(Product::get()->pluck('name', 'id')),
        ];
    }


    protected function getViewData(): array
    {
        $store_id = $this->getTable()->getFilters()['store_id']->getState()['value'] ?? 'all';
        // $store_id = __filament_request_select('store_id', 'all');
        $supplier_id = __filament_request_select('supplier_id', 'all');
        // $product_id = $this->getTable()->getFilters()['product_id']->getState()['values'] ?? 'all';
        $product_id = 'all';

        $stores_report_data = $this->getStoresReportData($product_id, $store_id, $supplier_id);
        // dd($stores_report_data, $store_id, $product_id);


        return [
            'stores_report_data' => $stores_report_data,
            'store_id' => $store_id,
            'supplier_id' => $supplier_id,
        ];
    }

    protected function getTableFiltersLayout(): ?string
    {
        return FiltersLayout::AboveContent;
    }

    public function getStoresReportData($product_id, $store_id, $supplier_id)
    {

        $subquery1 = DB::table('purchase_invoice_details')
            ->select([
                'purchase_invoice_details.product_id AS product_id',
                DB::raw("IF(JSON_VALID(products.name), REPLACE(JSON_EXTRACT(products.name, '$." . app()->getLocale() . "'), '\"', ''), products.name) AS product_name"),
                'units.name AS unit_name',
                DB::raw('SUM(purchase_invoice_details.quantity) AS purchase_quantity')
            ])
            ->join('purchase_invoices', 'purchase_invoice_details.purchase_invoice_id', '=', 'purchase_invoices.id')
            ->join('products', 'purchase_invoice_details.product_id', '=', 'products.id')
            ->join('units', 'purchase_invoice_details.unit_id', '=', 'units.id');
        if (isset($store_id) && $store_id != '' && $store_id != 0 && $store_id != 'all') {
            $subquery1->where('purchase_invoices.store_id', $store_id);
        }

        if (isset($supplier_id) && $supplier_id != '' && $supplier_id != 0 && $supplier_id != 'all') {
            $subquery1->where('purchase_invoices.supplier_id', $supplier_id);
        }
        $subquery1->whereNull('purchase_invoices.deleted_at');
        $subquery1 = $subquery1->groupBy('purchase_invoice_details.product_id', 'purchase_invoice_details.unit_id', 'products.name', 'units.name');

        $subquery2 = DB::table('orders_details')
            ->select([
                'orders_details.product_id AS product_id',
                DB::raw('SUM(orders_details.available_quantity) AS ordered_quantity')
            ])
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            // ->where('orders.created_at', '>=', DB::raw("DATE('2024-03-11')"))
            // ->whereIn('orders.status', [
            //     Order::READY_FOR_DELEVIRY,
            //     Order::DELEVIRED
            // ])
            // ->where('orders.active', 1)
            ->whereNull('orders.deleted_at')
            ->groupBy('orders_details.product_id', 'orders_details.unit_id');

        $query = DB::table(DB::raw("({$subquery1->toSql()}) AS p"))
            ->leftJoin(DB::raw("({$subquery2->toSql()}) AS o"), 'p.product_id', '=', 'o.product_id')
            ->mergeBindings($subquery1)
            ->mergeBindings($subquery2)
            ->select([
                'p.product_id',
                'p.product_name',
                'p.unit_name',
                DB::raw('COALESCE(p.purchase_quantity, 0) AS income'),
                DB::raw('COALESCE(o.ordered_quantity, 0) AS ordered'),
                DB::raw('(COALESCE(p.purchase_quantity, 0) - COALESCE(o.ordered_quantity, 0)) AS remaining')
            ]);


        $results = $query->get();
        // $results2 = Product::where('active',1)->select('id','name')->get()->toArray();
        // foreach ($results2 as $key => $value) { 
        //      $obj = new \stdClass();
        //      $obj->product_id = $value['product_id'];
        //      $obj->product_name = $value['product_name'];
        //      $obj->unit_name = null;
        //      $obj->income = null;
        //      $obj->ordered = null;
        //      $obj->remaining = null;
        //      $results3[] = $obj;
        // }
        // // dd($results,$results2,$results3);
        return $results;
    }

    protected function getActions(): array
    {
        return  [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')
            ->color('success'),];
    }

    public function exportToPdf()
    {
        $data = $this->getViewData();

        $data = [
            'stores_report_data' => $data['stores_report_data'],
            'store_id' => $data['store_id'],
            'supplier_id' => $data['supplier_id'],
        ];

        $pdf = Pdf::loadView('export.reports.stores-report', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("stores-report" . '.pdf');
            }, "stores-report" . '.pdf');
    }
}
