<?php

namespace App\Services\Warnings\Support;

use App\Filament\Clusters\SupplierStoresReportsCluster\Resources\MinimumProductQtyReportResource;

final class ReportUrlResolver
{
    public function lowStockReport(int $storeId): string
    {
        try {
            $url = MinimumProductQtyReportResource::getUrl('index', ['store_id' => $storeId]);
            if (!is_string($url) || $url === '') {
                throw new \RuntimeException('Empty Filament URL');
            }
            return $url;
        } catch (\Throwable) {
            // عدّل اسم المسار إن كان لديك Route صريح للتقرير
            return route('reports.minimum-product-qty.index', ['store_id' => $storeId]);
        }
    }
}
