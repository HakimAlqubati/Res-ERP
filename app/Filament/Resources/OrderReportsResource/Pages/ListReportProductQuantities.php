<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
use App\Models\Branch;
use App\Models\Product;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class ListReportProductQuantities extends ListRecords
{
    protected static string $resource = ReportProductQuantitiesResource::class;

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['product'] . '-' . $attributes['branch'] . '-' . $attributes['unit'];
    }
    // protected static string $view = 'filament.pages.order-reports.report-product-quantities';

    protected function getTableFilters(): array
    {
        return [

            SelectFilter::make("product_id")
                ->label(__('lang.product'))
                ->searchable()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Product::where('active', 1)
                    ->get()->pluck('name', 'id')),
            SelectFilter::make("branch_id")
                ->label(__('lang.branch'))
                ->multiple()
                ->query(function (Builder $q, $data) {
                    return $q;
                })->options(Branch::where('active', 1)
                    ->get()->pluck('name', 'id')),
            Filter::make('date')
                ->form([
                    DatePicker::make('start_date')
                        ->label(__('lang.start_date')),
                    DatePicker::make('end_date')
                        ->label(__('lang.end_date')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query;
                }),
        ];
    }

    protected function getViewData(): array
    {
        $branch_ids = [];
        $total_quantity = 0;
        $total_price = 0;
        $report_data['data'] = [];
        $product_id = __filament_request_select('product_id', 'choose');
        $branch_ids = __filament_request_select_multiple('branch_id', [], true);

        $start_date = __filament_request_key("date.start_date", null);

        $end_date = __filament_request_key("date.end_date", null);
        // dd($product_id,$start_date,$end_date);
        $report_data = $this->getReportData($product_id, $start_date, $end_date, $branch_ids);

        if (isset($report_data['total_price'])) {
            $total_price = $report_data['total_price'];
        }
        if (isset($report_data['total_quantity'])) {
            $total_quantity = $report_data['total_quantity'];
        }

        // dd($report_data);

        $start_date = (!is_null($start_date) ? date('Y-m-d', strtotime($start_date)) : __('lang.date_is_unspecified'));
        $end_date = (!is_null($end_date) ? date('Y-m-d', strtotime($end_date)) : __('lang.date_is_unspecified'));

        return [
            'report_data' => $report_data['data'],
            'product_id' => $product_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_quantity' => $total_quantity,
            'total_price' => $total_price,
        ];
    }

    // protected function getTableFiltersLayout(): ?string
    // {
    //     return \Filament\Tables\Enums\FiltersLayout::AboveContent;
    // }

    public function getReportData($product_id, $start_date, $end_date, $branch_ids)
    {
        // $currnetRole = getCurrentRole();
        // if ($currnetRole == 7)
        //     $branch_id = [getBranchId()];
        // else
        //     $branch_id = explode(',', $request->input('branch_id'));

        $subquery = DB::table('orders_details')
            ->select(
                'products.name AS product',
                'products.id AS product_id',
                'branches.name AS branch',
                'units.name AS unit',
                DB::raw('SUM(orders_details.available_quantity) AS quantity'),
                DB::raw('SUM(orders_details.available_quantity * orders_details.price) AS price'),
                DB::raw('MIN(orders_details.id) AS order_id') // Get the lowest order_details.id
            )
            ->join('products', 'orders_details.product_id', '=', 'products.id')
            ->join('orders', 'orders_details.order_id', '=', 'orders.id')
            ->join('branches', 'orders.branch_id', '=', 'branches.id')
            ->join('units', 'orders_details.unit_id', '=', 'units.id')
            ->whereNull('orders.deleted_at')
            ->groupBy(
                'products.id',
                'products.name',
                'orders.branch_id',
                'branches.name',
                'units.id',
                'units.name',
                'orders_details.price'
            );

        // Wrap in an outer query to enforce ordering
        $data = DB::table(DB::raw("({$subquery->toSql()}) as grouped_data"))
            ->mergeBindings($subquery) // Ensure bindings are passed correctly
            ->orderBy('order_id', 'asc') // Now order by order_id (MIN(orders_details.id))
            ->limit(10)
            ->offset(0)
            ->get();


        $final['data'] = [];
        $total_price = 0;
        $total_quantity = 0;
        foreach ($data as $val) {
            $obj = new \stdClass();
            $obj->product = $val->product;
            $obj->branch = $val->branch;
            $obj->unit = $val->unit;
            $obj->quantity = number_format($val->quantity, 2);
            $obj->price = number_format($val->price, 2);
            $total_price += $val->price;
            $total_quantity += $val->quantity;
            $final['data'][] = $obj;
        }

        $final['total_price'] = number_format($total_price, 2);
        $final['total_quantity'] = number_format($total_quantity, 2);

        return $final;
    }

    protected function getActions(): array
    {
        return [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')
            ->color('success')];
    }

    public function exportToPdf()
    {

        $data = $this->getViewData();

        $data = [
            'report_data' => $data['report_data'],
            'product_id' => $data['product_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_quantity' => $data['total_quantity'],
            'total_price' => $data['total_price'],
        ];

        $pdf = Pdf::loadView('export.reports.report-product-quantities', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("report-product-quantities" . '.pdf');
            }, "report-product-quantities" . '.pdf');
    }
}
