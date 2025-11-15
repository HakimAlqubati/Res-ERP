<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\ItemSalesByClassImport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ItemSalesImportController extends Controller
{
    /**
     * Handle the Excel import for Item Sales By Class.
     *
     * POST /api/item-sales/import
     */
    public function __invoke(Request $request): JsonResponse
    {
        // التحقق من وجود الملف وصحة الامتداد
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480', // 20MB
        ]);

        try {
            Excel::import(new ItemSalesByClassImport, $request->file('file'));

            return response()->json([
                'status'  => 'success',
                'message' => 'تم استيراد الفئات والمنتجات بنجاح (بدون كميات).',
            ]);
        } catch (Throwable $e) {
            // يمكنك تسجيل الخطأ في اللوج إن أحببت
            // \Log::error('ItemSales import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'حدث خطأ أثناء الاستيراد.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
