<?php

namespace App\Services\HR\ImageRecognize;

use App\DTOs\HR\ImageRecognize\EmployeeMatch;
use App\Repositories\HR\ImageRecognize\EmployeeRecognitionRepository;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;  

class FaceRecognitionService
{
    public function __construct(
        protected RekognitionClient $rekognition,
        protected EmployeeRecognitionRepository $repo,
        protected array $config = [],
    ) {
        $this->config = $this->config ?: config('rekognition');
    }

    public function identify(UploadedFile $file): EmployeeMatch
    {
        // 1) رفع الصورة مرة واحدة فقط
        $path = $this->uploadToS3($file);

        // 2) تأكيد الحجم > 0
        $sizeInBytes = Storage::disk('s3')->size($path);
        if ($sizeInBytes <= 0) {
            Log::warning('S3 object is empty', ['path' => $path]);
            return EmployeeMatch::notFound();
        }

        // 3) البحث مع إعادة المحاولة الذكية
        $bucket = $this->config['bucket'];
        $collectionId = $this->config['collection_id'];

        $maxRetries   = (int) $this->config['max_retries'];
        $backoffMs    = (int) $this->config['retry_backoff_ms'];
        $jitterMs     = (int) $this->config['retry_jitter_ms'];

        $baseThreshold = (float) $this->config['face_match_threshold'];
        $step          = (float) $this->config['threshold_step'];
        $minThreshold  = (float) $this->config['min_threshold'];

        $attempt = 0;
        $result  = null;

        do {
            $threshold = $this->thresholdForAttempt($attempt, $baseThreshold, $step, $minThreshold);

            try {
                $result = $this->rekognition->searchFacesByImage([
                    'CollectionId'       => $collectionId,
                    'Image'              => [
                        'S3Object' => [
                            'Bucket' => $bucket,
                            'Name'   => $path,
                        ],
                    ],
                    'FaceMatchThreshold' => $threshold,
                    'MaxFaces'           => (int) $this->config['max_faces'],
                ]);
            } catch (\Throwable $e) {
                // في حال أخطاء مؤقتة من AWS، انتظر وأعد المحاولة
                $this->sleepWithBackoff($attempt, $backoffMs, $jitterMs);
                Log::warning('Rekognition call failed; will retry if attempts left', [
                    'attempt'   => $attempt,
                    'max'       => $maxRetries,
                    'message'   => $e->getMessage(),
                ]);
                $attempt++;
                continue;
            }

            $matches = $result['FaceMatches'] ?? [];

            Log::info('rekognition_attempt', [
                'attempt'    => $attempt,
                'threshold'  => $threshold,
                'matchCount' => count($matches),
            ]);

            // وجدنا تطابق → اخرج فورًا
            if (!empty($matches)) {
                $top = $matches[0];
                $rekognitionId = $top['Face']['FaceId'] ?? null;
                $similarity    = isset($top['Similarity']) ? (float) $top['Similarity'] : null;
                $confidence    = isset($top['Face']['Confidence']) ? (float) $top['Face']['Confidence'] : null;

                if ($rekognitionId) {
                    [$name, $employeeId, $employee] = $this->repo->resolveByRekognitionId($rekognitionId);

                    if ($employeeId || $name) {
                        return new EmployeeMatch(true, $name, $employeeId, $employee, $similarity, $confidence);
                    }

                    return new EmployeeMatch(false, 'No mapping found', null, null, $similarity, $confidence);
                }
            }

            // لا يوجد تطابق → انتظر ثم أعد المحاولة إن بقيت محاولات
            if ($attempt < $maxRetries) {
                $this->sleepWithBackoff($attempt, $backoffMs, $jitterMs);
            }

            $attempt++;
        } while ($attempt <= $maxRetries);

        // بعد كل المحاولات: لا يوجد تطابق
        return EmployeeMatch::notFound();
    }

    protected function uploadToS3(UploadedFile $file): string
    {
        $prefix   = trim($this->config['upload_prefix'] ?? 'uploads', '/');
        $ext      = $file->getClientOriginalExtension();
        $timestamp= now()->format('Ymd_His_u');
        $path     = "{$prefix}/identify_face_{$timestamp}.{$ext}";

        Storage::disk('s3')->put($path, fopen($file->getRealPath(), 'r'), [
            'visibility'  => $this->config['visibility'] ?? 'private',
            'ContentType' => $file->getMimeType(),
        ]);

        Log::info('S3 Upload Info', [
            'file' => $path,
            'mime' => $file->getMimeType(),
        ]);

        return $path;
    }

    /** عتبة المحاولة: تقل تدريجيًا ولكن لا تقل عن حد أدنى */
    protected function thresholdForAttempt(int $attempt, float $base, float $step, float $min): float
    {
        $thr = $base - ($attempt * $step);
        return max($thr, $min);
    }

    /** انتظار بأسلوب Exponential Backoff + Jitter (محاولة 0 لا تنتظر) */
    protected function sleepWithBackoff(int $attempt, int $backoffMs, int $jitterMs): void
    {
        if ($attempt <= 0) return;
        $exp = $backoffMs * (2 ** ($attempt - 1));
        $jitter = random_int(0, max(0, $jitterMs));
        $sleepMs = $exp + $jitter;
        usleep($sleepMs * 1000);
    }
}
