<?php

namespace App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryValuationReportResource\Pages;

use App\Exports\StocktakeValuationReportExport;
use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\StockInventoryValuationReportResource;
use App\Filament\Traits\HasBackButtonAction;
use App\Modules\Stock\Reports\StockInventoryValuationReport\Contracts\StockInventoryValuationServiceInterface;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStockInventoryValuationReport extends ListRecords
{
    use HasBackButtonAction;

    protected static string $resource = StockInventoryValuationReportResource::class;

    protected string $view = 'filament.pages.inventory-reports.stock-inventory-valuation-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters();
        $filterState = $filters['valuation_filter']->getState() ?? [];

        $storeId       = $filterState['store_id'] ?? null;
        $inventoryDate = $filterState['inventory_date'] ?? null;

        if (! $storeId || ! $inventoryDate) {
            return [
                'storeId'       => $storeId,
                'inventoryDate' => $inventoryDate,
                'reportData'    => null,
            ];
        }

        $service    = app(StockInventoryValuationServiceInterface::class);
        $reportData = $service->getReport((int) $storeId, (string) $inventoryDate);

        return [
            'storeId'       => $storeId,
            'inventoryDate' => $inventoryDate,
            'reportData'    => $reportData,
        ];
    }

    /**
     * Export the valuation report to Excel via backend Maatwebsite Excel.
     */
    public function exportExcel(): ?BinaryFileResponse
    {
        $data       = $this->getViewData();
        $reportData = $data['reportData'] ?? null;

        if (! $reportData || empty($reportData->items)) {
            Notification::make()
                ->title('No data to export')
                ->warning()
                ->send();

            return null;
        }

        $storeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $reportData->storeName);
        $fileName  = "stocktake_valuation_{$storeName}_{$reportData->inventoryDate}.xlsx";

        return Excel::download(
            new StocktakeValuationReportExport($reportData),
            $fileName
        );
    }

    /**
     * Export the valuation report to PDF via backend mPDF.
     */
    public function exportPdf(): ?StreamedResponse
    {
        $data       = $this->getViewData();
        $reportData = $data['reportData'] ?? null;

        if (! $reportData || empty($reportData->items)) {
            Notification::make()
                ->title('No data to export')
                ->warning()
                ->send();

            return null;
        }

        $tempDir = storage_path('app/mpdf');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $html = view('export.reports.inventory.stocktake-valuation-report-pdf', [
            'reportData' => $reportData,
        ])->render();

        // Temporarily suppress internal mPDF PHP 8+ warnings (e.g. Otl.php line 5635 undefined key)
        $previousErrorHandler = set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if ($errno === E_WARNING && str_contains($errfile, 'mpdf')) {
                return true;
            }
            return false;
        });

        try {
            $mpdf = new Mpdf([
                'mode'             => 'utf-8',
                'format'           => 'A4',
                'tempDir'          => $tempDir,
                'autoScriptToLang' => true,
                'autoLangToFont'   => true,
                'default_font'     => 'dejavusans',
            ]);

            $mpdf->WriteHTML($html);
            $pdfContent = $mpdf->Output('', Destination::STRING_RETURN);
        } finally {
            restore_error_handler();
        }

        $storeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $reportData->storeName);
        $fileName  = "stocktake_valuation_{$storeName}_{$reportData->inventoryDate}.pdf";

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
