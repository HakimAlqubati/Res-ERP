<?php

namespace App\Filament\Resources\OrderReportsResource\Pages;

use App\Filament\Resources\OrderReportsResource\ReportProductQuantitiesResource;
use App\Models\Branch;
use App\Models\Order;
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
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf  as PDF;

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

        $pdf = PDF::loadView('export.reports.report-product-quantities', $data);

        return response()
            ->streamDownload(function () use ($pdf) {
                $pdf->stream("report-product-quantities" . '.pdf');
            }, "report-product-quantities" . '.pdf');
    }
}
