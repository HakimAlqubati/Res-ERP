<?php

namespace App\Http\Controllers\AWS\Textract;

use App\Http\Controllers\Controller;
use App\Services\AWS\Textract\AnalyzeExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OcrController extends Controller
{
    public function __construct(private AnalyzeExpenseService $svc) {}

    public function __invoke(Request $request)
    { 
        // التحقق من الملف (نفس قواعدك السابقة)
        $request->validate([
            'file' => 'required|file|max:30720', // 30MB
        ]);

        try {
            $out = $this->svc->analyze($request->file('file'));

            return response()->json([
                'success'   => true,
                'message'   => 'تم تحليل المستند باستخدام AnalyzeExpense.',
                'mime'      => $out['mime'],
                'documents' => $out['documents'],
                // 'raw' => ... (للتصحيح عند الحاجة)
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تحليل المستند عبر AnalyzeExpense.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
