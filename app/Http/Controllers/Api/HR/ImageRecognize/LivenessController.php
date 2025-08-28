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

        $file = $request->file('image');
        $pythonUrl = rtrim(config('services.python.base_url'), '/') . '/api/liveness';

        // تحكم ديناميكي من الكلاينت إن رغبت: ?max_attempts=5&base_delay_ms=250
        $maxAttempts = max(1, (int)$request->input('max_attempts', 3));
        $baseDelayMs = max(0, (int)$request->input('base_delay_ms', 200));

        $attempt = 0;
        $lastResult = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $resp = Http::timeout(10)
                    ->asMultipart()
                    ->attach(
                        'image',
                        file_get_contents($file->getRealPath()),
                        $file->getClientOriginalName()
                    )
                    ->post($pythonUrl);

                if (!$resp->ok()) {
                    Log::warning('Python liveness non-200', [
                        'status' => $resp->status(),
                        'body'   => $resp->body(),
                        'attempt'=> $attempt,
                    ]);
                    $lastResult = ['status' => $resp->status(), 'body' => $resp->body()];
                } else {
                    $json = $resp->json();
                    $lastResult = $json;

                    // إذا رجعت مثل:
                    // { "landmarks": {...}, "liveness": false, "score": 0.66 }
                    // نعيد المحاولة تلقائياً حتى maxAttempts أو حتى liveness=true
                    if (isset($json['liveness']) && $json['liveness'] === true) {
                        return response()->json([
                            'status'   => 'ok',
                            'attempts' => $attempt,
                            'result'   => $json,
                             'message'  => 'Liveness  confirmed after ('.$attempt.') retries.',
                        ], 200);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Python liveness call failed', [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);
                $lastResult = ['error' => $e->getMessage()];
            }

            // backoff بسيط مع jitter خفيف بين المحاولات
            if ($attempt < $maxAttempts) {
                usleep(($baseDelayMs + random_int(0, $baseDelayMs)) * 1000);
            }
        }

        // لم تتحقق الـ liveness بعد كل المحاولات
        return response()->json([
            'status'   => 'no_match',
            'attempts' => $attempt,
            'result'   => $lastResult,
            'message'  => 'Liveness not confirmed after ('.$attempt.') retries.',
        ], 200);
    }
}
