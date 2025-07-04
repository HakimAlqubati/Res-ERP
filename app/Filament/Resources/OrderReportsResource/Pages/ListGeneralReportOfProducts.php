<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\GeneralReportOfProductsResource;

use App\Models\Branch;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;
use Filament\Pages\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ListGeneralReportOfProducts extends ListRecords
{
    protected static string $resource = GeneralReportOfProductsResource::class;
    protected static string $view = 'filament.pages.order-reports.general-report-products';



    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['category_id'];
        return $attributes['product'] . '-' . $attributes['branch'] . '-' . $attributes['unit'];
    }



    protected function getViewData(): array
    {
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;

        $start_date = $this->getTable()->getFilters()['date']->getState()['start_date'];
        $end_date = $this->getTable()->getFilters()['date']->getState()['end_date'];

        $report_data['data'] = [];
        $total_quantity = 0;
        $total_price = 0;

        // $report_data  = $this->getReportData($start_date, $end_date, $branch_id);
        $report_data  = GeneralReportOfProductsResource::processReportData($start_date, $end_date, $branch_id);


        // dd($report_data);
        $start_date = (!is_null($start_date) ? date('Y-m-d', strtotime($start_date))  : __('lang.date_is_unspecified'));
        $end_date = (!is_null($end_date) ? date('Y-m-d', strtotime($end_date))  : __('lang.date_is_unspecified'));
        if (isset($report_data['total_price'])) {
            $total_price = $report_data['total_price'];
        }
        if (isset($report_data['total_quantity'])) {
            $total_quantity = $report_data['total_quantity'];
        }



        // dd($branch_id,$dd);
        return [
            'report_data' => $report_data['data'],
            'branch_id' => $branch_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_quantity' =>  $total_quantity,
            'total_price' =>  $total_price
        ];

        return [
            'report_data' => $report_data['data'],
            'branch_id' => $branch_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_quantity' =>  $total_quantity,
            'total_price' =>  $total_price
        ];
    }



    function getReportData($start_date, $end_date, $branch_id)
    {

        $get_data = DB::table('orders_details')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->select(
                'products.category_id',
                DB::raw('SUM(orders_details.available_quantity) as available_quantity'),
                // DB::raw('SUM(orders_details.price) as price'),
                'orders_details.price as price',
                DB::raw('SUM(orders_details.available_quantity) * orders_details.price as total_price')
            )

            ->when($branch_id, function ($query) use ($branch_id) {
                return $query->where('orders.branch_id', $branch_id);
            })
            ->when($start_date && $end_date, function ($query) use ($start_date, $end_date) {

                $s_d = date('Y-m-d', strtotime($start_date)) . ' 00:00:00';
                $e_d = date('Y-m-d', strtotime($end_date)) . ' 23:59:59';

                return $query->whereBetween('orders.created_at', [$s_d, $e_d]);
            })
            ->whereNull('orders.deleted_at')
            ->groupBy(
                'products.category_id',
                'orders_details.price',
                'orders_details.unit_id'
            )
            ->get()
            ->toArray();
        $sum_price = 0;
        $sum_qty = 0;

        $data = [];
        foreach ($get_data as $val) {
            if (!isset($data[$val->category_id])) {
                $data[$val->category_id] = [
                    'price' => 0,
                    'available_quantity' => 0,
                ];
            }
            $data[$val->category_id]['price'] += $val->total_price;
            $data[$val->category_id]['available_quantity'] += $val->available_quantity;
        }

        $categories = DB::table('categories')->where('active', 1)->get(['id', 'name'])->pluck('name', 'id');

        $final_result['data'] = [];
        $total_price = 0;
        $total_quantity = 0;
        foreach ($categories as $cat_id => $cat_name) {
            $obj = new \stdClass();
            $obj->category_id = $cat_id;
            $obj->url_report_details = "admin/general-report-products/details/$cat_id?start_date=$start_date&end_date=$end_date&branch_id=$branch_id&category_id=$cat_id'";
            $obj->category = $cat_name;
            $obj->quantity =  round(isset($data[$cat_id]) ? $data[$cat_id]['available_quantity'] : 0, 0);
            $price = (isset($data[$cat_id]) ? $data[$cat_id]['price'] : '0.00');
            $obj->price = $price;
            $obj->amount = number_format($price, 2);
            $obj->symbol = getDefaultCurrency();
            $total_price += $price;
            $total_quantity += $obj->quantity;
            $final_result['data'][] = $obj;
        }
        $final_result['total_price'] = $total_price;
        $final_result['total_quantity'] = number_format($total_quantity, 2);
        return [];
        return $final_result;
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
            'report_data' => $data['report_data'],
            'branch_id' => $data['branch_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_quantity' => $data['total_quantity'],
            'total_price' => $data['total_price']
        ];


        $pdf = PDF::loadView('export.reports.general-report-products', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("general-report-products" . '.pdf');
            }, "general-report-products" . '.pdf');
    }
}