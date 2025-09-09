<?php

namespace App\Http\Controllers\Api\HR\ImageRecognize;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LivenessController extends Controller
{
    /**
     * POST /api/hr/image-recognize/liveness
     *
     * Query params (اختيارية):
     * - max_attempts   (int)   [1..6]   افتراضي 3
     * - base_delay_ms  (int)   [50..2000] افتراضي 200
     * - min_score      (float) [0.50..0.99] افتراضي 0.80
     *
     * Body (multipart):
     * - image (jpg/jpeg/png, <= 5MB)
     */
    public function check(Request $request)
    {
        // تحقق المدخلات
        $request->validate([
            'image' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        // التهيئة العامة
        $corrId = (string) Str::uuid(); // correlation id لتمييز الطلب في اللوج
        $base   = config('services.python.base_url');

        
        if (!$base) {
            Log::error('Liveness: missing python base URL', ['corr' => $corrId]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Python base URL is not configured (services.python.base_url).',
                'corr_id' => $corrId,
            ], 500);
        }

        $pythonUrl = rtrim($base, '/') . '/api/liveness';

        // قيود آمنة على المدخلات
        $maxAttemptsReq = (int) $request->input('max_attempts', 1);
        $baseDelayMsReq = (int) $request->input('base_delay_ms', 200);
        $minScoreReq    = (float) $request->input('min_score', 0.70);

        $maxAttempts = max(1, min($maxAttemptsReq, 6));       // 1..6
        $baseDelayMs = max(50, min($baseDelayMsReq, 2000));   // 50..2000 ms
        $minScore    = max(0.50, min($minScoreReq, 0.99));    // 0.50..0.99

        $file = $request->file('image');
        $attempt = 0;
        $lastResult = null;
        $lastHttpStatus = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $handle = null;
            try {
                $handle = fopen($file->getRealPath(), 'r');

                $resp = Http::timeout(15)
                    ->acceptJson()
                    ->asMultipart()
                    ->withHeaders([
                        'X-Correlation-ID' => $corrId,
                    ])
                    ->attach('image', $handle, $file->getClientOriginalName())
                    ->post($pythonUrl);
                    // dd($resp->body(),$pythonUrl);

                // أغلق الهاندل بعد الإرسال
                if (is_resource($handle)) {
                    fclose($handle);
                    $handle = null;
                }

                $lastHttpStatus = $resp->status();

                if (!$resp->ok()) {
                    Log::warning('Liveness upstream non-200', [
                        'corr'    => $corrId,
                        'status'  => $resp->status(),
                        'body'    => mb_substr($resp->body(), 0, 2000),
                        'attempt' => $attempt,
                    ]);

                    $lastResult = [
                        'status' => $resp->status(),
                        'body'   => mb_substr($resp->body(), 0, 2000),
                    ];
                } else {
                    // JSON آمن
                    $json = null;
                    try {
                        $json = $resp->json();
                    } catch (\Throwable $e) {
                        $json = null;
                    }

                    if (!is_array($json)) {
                        Log::warning('Liveness invalid JSON', [
                            'corr'    => $corrId,
                            'attempt' => $attempt,
                            'body'    => mb_substr($resp->body(), 0, 500),
                        ]);
                        $lastResult = ['error' => 'Invalid JSON from Python service'];
                    } else {
                        $lastResult = $json;

                        // استخراج liveness
                        $hasLive = array_key_exists('liveness', $json) ? (bool) $json['liveness'] : false;

                        // تطبيع score بأمان
                        $scoreVal = null;
                        if (isset($json['score'])) {
                            $tmp = (float) $json['score'];
                            if (!is_nan($tmp) && $tmp >= 0) {
                                $scoreVal = $tmp;
                            }
                        }

                        // شرط النجاح: liveness=true AND score>=minScore
                        if ($hasLive && $scoreVal !== null && $scoreVal >= $minScore) {
                            return response()->json([
                                'status'    => 'ok',
                                'attempts'  => $attempt,
                                'result'    => $json,
                                'min_score' => $minScore,
                                'corr_id'   => $corrId,
                                'message'   => "Liveness confirmed after {$attempt} attempt(s) with score {$scoreVal} (≥ {$minScore}).",
                            ], 200);
                        }

                        // لوج معلوماتي لتفسير سبب إعادة المحاولة
                        Log::info('Liveness not sufficient; will retry', [
                            'corr'      => $corrId,
                            'attempt'   => $attempt,
                            'hasLive'   => $hasLive,
                            'score'     => $scoreVal,
                            'threshold' => $minScore,
                        ]);
                        // الاستمرار للمحاولة التالية (حتى حدّ المحاولات)
                    }
                }
            } catch (\Throwable $e) {
                if (is_resource($handle)) {
                    fclose($handle);
                }

                Log::warning('Liveness call failed', [
                    'corr'    => $corrId,
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
                $lastResult = ['error' => $e->getMessage()];
                $lastHttpStatus = null;
            }

            // Exponential backoff + jitter (مع سقف 8 ثوانٍ)
            if ($attempt < $maxAttempts) {
                $expDelay = $baseDelayMs * (2 ** ($attempt - 1)); // 200, 400, 800, ...
                $jitter   = random_int(0, (int) ($baseDelayMs * 0.5));
                $sleepMs  = min($expDelay + $jitter, 8000);
                usleep($sleepMs * 1000);
            }
        }

        // لم تتحقق liveness ضمن المحاولات
        // إن كان آخر سبب خطأ اتصال/خدمة بايثون (non-200/timeout/exception) => 502
        if (($lastHttpStatus && $lastHttpStatus >= 500) || isset($lastResult['error'])) {
            return response()->json([
                'status'    => 'upstream_error',
                'attempts'  => $attempt,
                'min_score' => $minScore,
                'result'    => $lastResult,
                'corr_id'   => $corrId,
                'message'   => 'No clear face found',
            ], 502);
        }

        // خلاف ذلك، النتيجة غير كافية (liveness=false أو score < threshold) => 422
        $failureMessage = "Liveness not confirmed after {$attempt} attempt(s) with required score ≥ {$minScore}.";
        if (is_array($lastResult) && isset($lastResult['message']) && is_string($lastResult['message'])) {
            $failureMessage .= " Python says: " . $lastResult['message'];
        }

        return response()->json([
            'status'    => 'no_match',
            'attempts'  => $attempt,
            'min_score' => $minScore,
            'result'    => $lastResult,
            'corr_id'   => $corrId,
            'message'   => $failureMessage,
        ], 422);
    }
}
