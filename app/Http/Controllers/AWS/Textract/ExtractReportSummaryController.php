<?php

namespace App\Http\Controllers\AWS\Textract;

use App\Http\Controllers\Controller;
use App\Services\AWS\Textract\ExtractReportSummaryService;
use Illuminate\Http\Request;

class ExtractReportSummaryController extends Controller
{
    public function __construct(
        private readonly ExtractReportSummaryService $service
    ) {}

    public function __invoke(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:30720'], // 30MB
        ]);

        try {
            $data = $this->service->extract($request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Report summary extracted successfully.',
                'data' => $data,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze report.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}