<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Products\ProductRepository;
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
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

class ListReportProductQuantities extends ListRecords
{
    protected static string $resource = ReportProductQuantitiesResource::class;

    // public function getTableRecordKey(Model $record): string
    // {
    //     $attributes = $record->getAttributes();
    //     return $attributes['product'] . '-' . $attributes['branch'] . '-' . $attributes['unit'];
    // }
    protected static string $view = 'filament.pages.order-reports.report-product-quantities';

    public function getTableRecordKey(Model $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['product'] . '-' . $attributes['branch'] . '-' . $attributes['unit'];
    }





    protected function getActions(): array
    {
        return [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')
            ->color('success')];
    }


    protected function getViewData(): array
    {
        $repo = app(ProductRepository::class);
        $branch_id = $this->getTable()->getFilters()['branch_id']->getState()['value'] ?? null;
        $start_date = $this->getTable()->getFilters()['date']->getState()['start_date'];
        $end_date = $this->getTable()->getFilters()['date']->getState()['end_date'];
        $product_id = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $data = $repo->getReportDataFromTransactions($product_id, $start_date, $end_date, $branch_id);
        // dd($product_id,$branch_id,$data[0]);       
// dd($data);
       return [
        'report_data' => $data,
        'product_id' => $product_id,
        'start_date' => $start_date,
        'end_date' => $end_date,
        // 'total_quantity' => $data['total_quantity']??0,
        // 'total_price' => 0,
       ];
        return [];
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

        $pdf = PDF::loadView('export.reports.report-product-quantities', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("report-product-quantities" . '.pdf');
            }, "report-product-quantities" . '.pdf');
    }
}
