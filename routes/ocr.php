<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Aws\Textract\TextractClient;
use Illuminate\Support\Str;

Route::post('/ocr', function (Request $request) {
    // 1) التحقق من الملف
    $request->validate([
        'file' => 'required|file|max:30720', // 30MB
    ]);

    $file  = $request->file('file');
    $mime  = $file->getMimeType();
    $isImg = str_starts_with($mime, 'image/');
    $isPdf = $mime === 'application/pdf';

    // 2) عميل Textract
    $textract = new TextractClient([
        'version'     => 'latest',
        'region'      => env('AWS_DEFAULT_REGION', 'me-central-1'),
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    $s3TempKey = null;

    try {
        // 3) تحضير مُدخل AnalyzeExpense
        $params = [];

        if ($isImg) {
            $bytes = file_get_contents($file->getRealPath());
            $params['Document'] = ['Bytes' => $bytes];
        } elseif ($isPdf) {
            $bucket = env('AWS_BUCKET');
            if (! $bucket) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم ضبط AWS_BUCKET. لا يمكن تحليل PDF بدون S3.',
                ], 500);
            }

            $s3TempKey = 'textract/tmp/'.now()->format('Y/m/d/').Str::uuid().'-'.$file->getClientOriginalName();
            Storage::disk('s3')->put($s3TempKey, file_get_contents($file->getRealPath()), 'private');

            $params['Document'] = [
                'S3Object' => [
                    'Bucket' => $bucket,
                    'Name'   => $s3TempKey,
                ],
            ];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'الرجاء رفع صورة (PNG/JPG/TIFF) أو ملف PDF فقط.',
            ], 422);
        }

        // 4) استدعاء AnalyzeExpense
        $result = $textract->analyzeExpense($params);

        // 5) تحويل النتيجة إلى صيغة عامة (بدون مفاتيح خاصة)
        $documents = $result['ExpenseDocuments'] ?? [];
        $parsed    = [];

        foreach ($documents as $doc) {
            // SummaryFields كقائمتين: مختصرة (type => value) ومفصلة (type/value/confidence)
            $summaryFields   = $doc['SummaryFields'] ?? [];
            $summaryMap      = []; // type => value
            $summaryDetailed = []; // [{type, value, confidence}...]

            foreach ($summaryFields as $sf) {
                $typeText = $sf['Type']['Text'] ?? ($sf['Type']['NormalizedValue']['Value'] ?? null);
                $value    = $sf['ValueDetection']['Text'] ?? null;
                $conf     = $sf['ValueDetection']['Confidence'] ?? null;

                if ($typeText !== null) {
                    $summaryMap[$typeText] = $value;
                    $summaryDetailed[] = [
                        'type'       => $typeText,
                        'value'      => $value,
                        'confidence' => $conf,
                    ];
                }
            }

            // Line Items
            $lineItems = [];
            foreach (($doc['LineItemGroups'] ?? []) as $lg) {
                foreach (($lg['LineItems'] ?? []) as $li) {
                    $row = [];
                    foreach (($li['LineItemExpenseFields'] ?? []) as $f) {
                        $ftype = $f['Type']['Text'] ?? null; // مثل: ITEM, QUANTITY, UNIT_PRICE, PRICE, TAX
                        $fval  = $f['ValueDetection']['Text'] ?? null;
                        if ($ftype !== null) {
                            $row[$ftype] = $fval;
                        }
                    }
                    if (!empty($row)) {
                        $lineItems[] = $row;
                    }
                }
            }

            $parsed[] = [
                'summary'        => $summaryMap,       // خريطة عامة: نوع الحقل => قيمته
                'summary_raw'    => $summaryDetailed,  // تفاصيل مع الثقة
                'line_items'     => $lineItems,        // صفوف العناصر
            ];
        }

        // 6) حذف ملف S3 المؤقت إن وُجد
        if ($s3TempKey) {
            try { Storage::disk('s3')->delete($s3TempKey); } catch (\Throwable $e) {}
        }

        return response()->json([
            'success'   => true,
            'message'   => 'تم تحليل المستند باستخدام AnalyzeExpense.',
            'mime'      => $mime,
            'documents' => $parsed,
            // 'raw'    => $result->toArray(), // فعّلها عند الحاجة للتصحيح
        ], 200);

    } catch (\Throwable $e) {
        if ($s3TempKey) {
            try { Storage::disk('s3')->delete($s3TempKey); } catch (\Throwable $ee) {}
        }

        return response()->json([
            'success' => false,
            'message' => 'فشل تحليل المستند عبر AnalyzeExpense.',
            'error'   => $e->getMessage(),
        ], 500);
    }
});
