<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\GeneralReportProductDetails;
use App\Filament\Resources\OrderReportsResource\Pages\ListGeneralReportOfProducts;
use App\Models\FakeModelReports\GeneralReportOfProducts;
use Filament\Resources\Resource;

class GeneralReportOfProductsResource extends Resource
{
    protected static ?string $model = GeneralReportOfProducts::class;
    protected static ?string $slug = 'general-report-products';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = ReportOrdersCluster::class;
    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.general_report_of_products');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.general_report_of_products');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneralReportOfProducts::route('/'),
            'details' => GeneralReportProductDetails::route('/details/{category_id}'),
        ];
    }
}
