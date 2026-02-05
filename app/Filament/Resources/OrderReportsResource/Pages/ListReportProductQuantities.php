<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use Filament\Actions\Action;
use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\Products\ProductRepository;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
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

    protected string $view = 'filament.pages.order-reports.report-product-quantities';

    public function getTableRecordKey(Model|array $record): string
    {
        $attributes = $record->getAttributes();
        return $attributes['product'] . '-' . $attributes['branch'] . '-' . $attributes['unit'];
    }





    protected function getActions(): array
    {
        return [];
        return [Action::make('Export to PDF')->label(__('lang.export_pdf'))
            ->action('exportToPdf')
            ->color('success')];
    }


    protected function getViewData(): array
    {
        $repo = app(ProductRepository::class);
        $branchIds = $this->getTable()->getFilters()['branch_id']->getState()['values'];
        $start_date = $this->getTable()->getFilters()['date']->getState()['start_date'];
        $end_date = $this->getTable()->getFilters()['date']->getState()['end_date'];
        $product_id = $this->getTable()->getFilters()['product_id']->getState()['value'] ?? null;
        $categoryIds = $this->getTable()->getFilters()['category_id']->getState()['values'] ?? [];
        if (count($branchIds) <= 0) {
            $branchIds = Branch::whereIn('type', [
                Branch::TYPE_BRANCH,
                Branch::TYPE_CENTRAL_KITCHEN,
                Branch::TYPE_POPUP
            ])
                ->activePopups()
                ->active()->pluck('id');
        }
        $data = $repo->getReportDataFromOrdersDetails($product_id, $start_date, $end_date, $branchIds, $categoryIds);

        // $totalPrice = $data->sum('price');
        return [
            'report_data' => $data,
            'product_id' => $product_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
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
