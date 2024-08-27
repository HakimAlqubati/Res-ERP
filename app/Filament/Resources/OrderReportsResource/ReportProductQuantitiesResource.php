<?php

namespace App\Filament\Resources\OrderReportsResource;

use App\Filament\Clusters\MainOrdersCluster;
use App\Filament\Clusters\ReportOrdersCluster;
use App\Filament\Resources\OrderReportsResource\Pages\ListReportProductQuantities;
use App\Models\FakeModelReports\ReportProductQuantities;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;

class ReportProductQuantitiesResource extends Resource
{
    protected static ?string $model = ReportProductQuantities::class;
    protected static ?string $slug = 'report-product-quantities';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = ReportOrdersCluster::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    
    /**
     * @deprecated Use `getModelLabel()` instead.
     */
    public static function getLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }
    public static function getNavigationLabel(): string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPluralLabel(): ?string
    {
        return __('lang.report_product_quantities');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportProductQuantities::route('/'),
        ];
    }
}
