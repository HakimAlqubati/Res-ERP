<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Aws\Textract\TextractClient;

/**
 * تنظيف UTF-8 عميق للمصفوفات/الكائنات/السلاسل للتأكد من أن json_encode لا يفشل.
 */
if (!function_exists('utf8_sanitize_deep')) {
    function utf8_sanitize_deep($value)
    {
        // دالة مساعدة لتحويل نص إلى UTF-8 صالح مع إزالة محارف التحكم
        $sanitizeString = function (string $s): string {
            // أزل محارف التحكم الخفية (عدا \n\r\t إن رغبت بالإبقاء عليها)
            $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s) ?? '';
            // لو الترميز غير UTF-8، حوّله
            if (!mb_detect_encoding($s, 'UTF-8', true)) {
                $s = mb_convert_encoding($s, 'UTF-8', 'auto');
            }
            // ضمان أخير: تجاهل أي بايتات مكسورة
            $s = @iconv('UTF-8', 'UTF-8//IGNORE', $s) ?: $s;
            return $s;
        };

        if (is_string($value)) {
            return $sanitizeString($value);
        }

        if (is_array($value)) {
            $clean = [];
            foreach ($value as $k => $v) {
                $ck = is_string($k) ? $sanitizeString($k) : $k;
                $clean[$ck] = utf8_sanitize_deep($v);
            }
            return $clean;
        }

        if (is_object($value)) {
            // كائنات AWS\Result ونحوها: حوّل لمصفوفة إن أمكن
            if (method_exists($value, 'toArray')) {
                return utf8_sanitize_deep($value->toArray());
            }
            // الكائنات العادية: حوّل لمصفوفة ثم نظّف
            return utf8_sanitize_deep((array) $value);
        }

        // الأنواع الأخرى (int/bool/null/float)
        return $value;
    }
}

Route::post('/ocr', function (Request $request) {
    // 1) التحقق من الملف والمدخلات
    $request->validate([
        'file' => 'required|file|max:30720', // 30MB
        'mode' => 'sometimes|in:sync,async',
    ]);

    $file    = $request->file('file');
    $mode    = $request->input('mode'); // null|sync|async
    $mime    = $file->getMimeType() ?: '';
    $isImage = str_starts_with($mime, 'image/');

    // 2) تهيئة عميل Textract
    $textract = new TextractClient([
        'region'      => env('AWS_DEFAULT_REGION', 'me-central-1'),
        'version'     => 'latest',
        'credentials' => [
            'key'    => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    try {
        // 3) اختيار أسلوب التحليل
        if ($mode === 'sync' && $isImage) {
            // ▶︎ SYNC (للصور فقط – أسرع)
            $bytes  = file_get_contents($file->getRealPath());
            $result = $textract->analyzeExpense([
                'Document' => ['Bytes' => $bytes],
            ]);
        } else {
            // ▶︎ ASYNC (موصى به للـ PDF أو متعددة الصفحات)
            $bucket = env('AWS_BUCKET');
            if (!$bucket) {
                return response()->json(
                    ['status' => 'error', 'error' => 'AWS_BUCKET is not set'],
                    500,
                    [],
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );
            }

            $key = 'invoices/' . now()->format('Y/m/d/') . uniqid('inv_') . '.' . ($file->getClientOriginalExtension() ?: 'bin');
            Storage::disk('s3')->put($key, file_get_contents($file->getRealPath()));

            // ابدأ المهمة
            $start = $textract->startExpenseAnalysis([
                'DocumentLocation' => [
                    'S3Object' => ['Bucket' => $bucket, 'Name' => $key],
                ],
            ]);

            $jobId = $start->get('JobId') ?? null;
            if (!$jobId) {
                return response()->json(
                    ['status' => 'error', 'error' => 'Failed to start Textract job'],
                    500,
                    [],
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );
            }

            // Poll (للتجربة فقط؛ في الإنتاج استخدم Queue/Webhook/Backoff)
            $status      = 'IN_PROGRESS';
            $maxAttempts = 60;  // ~120 ثانية
            $attempt     = 0;
            $result      = null;

            while ($status === 'IN_PROGRESS' && $attempt < $maxAttempts) {
                $attempt++;
                usleep(2_000_000); // 2s

                $resp   = $textract->getExpenseAnalysis([
                    'JobId'      => $jobId,
                    'MaxResults' => 1000,
                ]);
                $status = $resp->get('JobStatus');

                if ($status === 'SUCCEEDED') {
                    $result = $resp;
                    break;
                }

                if ($status === 'FAILED' || $status === 'PARTIAL_SUCCESS') {
                    return response()->json(
                        ['status' => 'error', 'error' => "Textract job status: {$status}"],
                        500,
                        [],
                        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                    );
                }
            }

            if ($status !== 'SUCCEEDED' || !$result) {
                return response()->json(
                    ['status' => 'error', 'error' => 'Textract timeout or not succeeded'],
                    504,
                    [],
                    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );
            }
        }

        // 4) تحويل النتيجة إلى رأس + بنود + متوسط ثقة
        [$header, $items, $confidenceAvg] = (function ($res) {
            $docs   = $res->get('ExpenseDocuments') ?? [];
            $head   = [];
            $lines  = [];
            $scores = [];

            foreach ($docs as $doc) {
                // SummaryFields = key/value للرأس
                foreach ($doc['SummaryFields'] ?? [] as $f) {
                    $type = $f['Type']['Text'] ?? null;
                    $val  = $f['ValueDetection']['Text'] ?? null;
                    $conf = $f['ValueDetection']['Confidence'] ?? null;

                    if ($val !== null) {
                        // خرائط مبسّطة للأسماء
                        $map = [
                            'INVOICE_RECEIPT_ID'   => 'invoice_number',
                            'INVOICE_RECEIPT_DATE' => 'invoice_date',
                            'VENDOR_NAME'          => 'vendor_name',
                            'VENDOR_ADDRESS'       => 'vendor_address',
                            'VENDOR_PHONE'         => 'vendor_phone',
                            'SUBTOTAL'             => 'subtotal',
                            'TAX'                  => 'tax_total',
                            'TOTAL'                => 'grand_total',
                            'AMOUNT_DUE'           => 'amount_due',
                            'INVOICE_RECEIPT_TYPE' => 'document_type',
                            'CURRENCY'             => 'currency',
                            'PAYMENT_TERMS'        => 'payment_terms',
                            'DUE_DATE'             => 'due_date',
                            'PURCHASE_ORDER'       => 'po_number',
                        ];
                        $key = $map[$type] ?? strtolower($type ?? 'field');

                        // إن تكرر النوع، احتفظ بالأعلى ثقة
                        if (!isset($head[$key]) || (($head[$key]['confidence'] ?? 0) < ($conf ?? 0))) {
                            $head[$key] = ['value' => $val, 'confidence' => $conf];
                        }
                        if ($conf !== null) {
                            $scores[] = $conf;
                        }
                    }
                }

                // LineItemGroups = البنود
                foreach ($doc['LineItemGroups'] ?? [] as $group) {
                    foreach ($group['LineItems'] ?? [] as $line) {
                        $row = [
                            'description' => null,
                            'sku'         => null,
                            'qty'         => null,
                            'unit_price'  => null,
                            'tax'         => null,
                            'total'       => null,
                            '_conf'       => [],
                        ];
                        foreach ($line['LineItemExpenseFields'] ?? [] as $lf) {
                            $t = $lf['Type']['Text'] ?? '';
                            $v = $lf['ValueDetection']['Text'] ?? '';
                            $c = $lf['ValueDetection']['Confidence'] ?? null;

                            if ($c !== null) {
                                $scores[] = $c;
                            }

                            if (in_array($t, ['ITEM', 'DESCRIPTION'], true)) {
                                $row['description'] = $v ?: $row['description'];
                            }
                            if ($t === 'SKU')      $row['sku']        = $v;
                            if ($t === 'QUANTITY') $row['qty']        = $v;
                            if ($t === 'PRICE')    $row['unit_price'] = $v;
                            if ($t === 'TAX')      $row['tax']        = $v;
                            if ($t === 'AMOUNT')   $row['total']      = $v;

                            if ($t && $c !== null) {
                                $row['_conf'][$t] = $c;
                            }
                        }
                        // تجاهل الصفوف الفارغة جداً
                        if ($row['description'] || $row['total'] || $row['qty']) {
                            $lines[] = $row;
                        }
                    }
                }
            }

            // تبسيط الرأس {key: value} + متوسط الثقة
            $simpleHead = [];
            foreach ($head as $k => $pair) {
                $simpleHead[$k] = $pair['value'];
            }

            $avg = count($scores) ? round(array_sum($scores) / count($scores), 2) : null;
            return [$simpleHead, $lines, $avg];
        })($result);

        // 5) بناء الحمولة
        $payload = [
            'status'         => 'ok',
            'provider'       => 'aws-textract:AnalyzeExpense',
            'confidence_avg' => $confidenceAvg,
            'header'         => $header,
            'items'          => $items,
        ];

        if ($request->boolean('debug')) {
            // raw قد يحتوي محارف مكسورة؛ نمرره عبر json_encode مع بدائل ثم نفكّه
            $rawArray = $result->toArray();
            $payload['raw'] = json_decode(
                json_encode($rawArray, JSON_INVALID_UTF8_SUBSTITUTE),
                true
            );
        }

        // 6) تنظيف UTF-8 قبل الإرجاع
        $payload = utf8_sanitize_deep($payload);

        // 7) إرجاع JSON بخيارات آمنة للعربية ومحارف مكسورة
        return response()->json(
            $payload,
            200,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (\Throwable $e) {
        $errorPayload = [
            'status'  => 'error',
            'message' => $e->getMessage(),
            // لا تعرض الـ trace إلا في debug=true لحماية المعلومات
            'trace'   => config('app.debug') || request()->boolean('debug') ? $e->getTrace() : null,
        ];

        // تنظيف قبل الإرجاع تفادياً لفشل JSON في رسائل الأخطاء نفسها
        $errorPayload = utf8_sanitize_deep($errorPayload);

        return response()->json(
            $errorPayload,
            500,
            [],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
});
