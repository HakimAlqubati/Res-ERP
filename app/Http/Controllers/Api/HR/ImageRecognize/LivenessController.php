<?php

namespace App\Http\Controllers\Api\HR\ImageRecognize;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LivenessController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $base = config('services.python.base_url');
        if (!$base) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Python base URL is not configured (services.python.base_url).',
            ], 500);
        }

        $pythonUrl = rtrim($base, '/') . '/api/liveness';

        // قيود آمنة على المدخلات
        $maxAttemptsReq = (int)$request->input('max_attempts', 3);
        $baseDelayMsReq = (int)$request->input('base_delay_ms', 200);

        $maxAttempts = max(1, min($maxAttemptsReq, 6));      // 1..6
        $baseDelayMs = max(50, min($baseDelayMsReq, 2000));  // 50..2000 ms

        $file = $request->file('image');
        $attempt = 0;
        $lastResult = null;
        $lastHttpStatus = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $resp = Http::timeout(15)
                    ->acceptJson()
                    ->asMultipart()
                    ->attach(
                        'image',
                        fopen($file->getRealPath(), 'r'),
                        $file->getClientOriginalName()
                    )
                    ->post($pythonUrl);

                $lastHttpStatus = $resp->status();

                if (!$resp->ok()) {
                    Log::warning('Python liveness non-200', [
                        'status'  => $resp->status(),
                        'body'    => $resp->body(),
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
                        Log::warning('Python liveness invalid JSON', [
                            'attempt' => $attempt,
                            'body'    => mb_substr($resp->body(), 0, 500),
                        ]);
                        $lastResult = ['error' => 'Invalid JSON from Python service'];
                    } else {
                        $lastResult = $json;

                        // نجاح مؤكد
                        if (array_key_exists('liveness', $json) && $json['liveness'] === true) {
                            return response()->json([
                                'status'    => 'ok',
                                'attempts'  => $attempt,
                                'result'    => $json,
                                'message'   => "Liveness confirmed after {$attempt} attempt(s).",
                            ], 200);
                        }

                        // إن كانت liveness=false أو غير موجودة، سنعيد المحاولة (حتى حدّ المحاولات)
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Python liveness call failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
                $lastResult = ['error' => $e->getMessage()];
                $lastHttpStatus = null;
            }

            // Exponential backoff + jitter
            if ($attempt < $maxAttempts) {
                $expDelay = $baseDelayMs * (2 ** ($attempt - 1)); // 200, 400, 800, ...
                $jitter   = random_int(0, (int)($baseDelayMs * 0.5));
                $sleepMs  = min($expDelay + $jitter, 8000); // سقف 8 ثوانٍ لكل انتظار
                usleep($sleepMs * 1000);
            }
        }

        // لم تتحقق liveness
        // إن كان السبب أعطال من خدمة بايثون (non-200/timeout)، أرجع 502.
        if (($lastHttpStatus && $lastHttpStatus >= 500) || isset($lastResult['error'])) {
            return response()->json([
                'status'    => 'upstream_error',
                'attempts'  => $attempt,
                'result'    => $lastResult,
                'message'   => 'Python service did not confirm liveness.',
            ], 502);
        }

        // خلاف ذلك، عدم تحقق liveness بعد المحاولات
        return response()->json([
            'status'    => 'no_match',
            'attempts'  => $attempt,
            'result'    => $lastResult,
            'message'   => "Liveness not confirmed after {$attempt} attempt(s).",
        ], 422);
    }
}
