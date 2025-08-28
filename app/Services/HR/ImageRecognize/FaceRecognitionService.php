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
        // 1) رفع الصورة إلى S3 باسم منظم
        $path = $this->uploadToS3($file);

        // 2) تأكيد الحجم > 0
        $sizeInBytes = Storage::disk('s3')->size($path);
        if ($sizeInBytes <= 0) {
            Log::warning('S3 object is empty', ['path' => $path]);
            return EmployeeMatch::notFound();
        }

        // 3) البحث عبر Rekognition
        $collectionId = $this->config['collection_id'];
        $bucket       = $this->config['bucket'];

        $result = $this->rekognition->searchFacesByImage([
            'CollectionId'       => $collectionId,
            'Image'              => [
                'S3Object' => [
                    'Bucket' => $bucket,
                    'Name'   => $path,
                ],
            ],
            'FaceMatchThreshold' => (float) $this->config['face_match_threshold'],
            'MaxFaces'           => (int) $this->config['max_faces'],
        ]);

        Log::info('rekognition_result', ['matches' => $result['FaceMatches'] ?? []]);

        $matches = $result['FaceMatches'] ?? [];
        if (empty($matches)) {
            return EmployeeMatch::notFound();
        }

        // أفضل تطابق
        $top = $matches[0];
        $rekognitionId = $top['Face']['FaceId'] ?? null;
        $similarity    = isset($top['Similarity']) ? (float) $top['Similarity'] : null;
        $confidence    = isset($top['Face']['Confidence']) ? (float) $top['Face']['Confidence'] : null;

        if (!$rekognitionId) {
            return EmployeeMatch::notFound();
        }

        // 4) ربط RekognitionId → DynamoDB → Employee
        [$name, $employeeId, $employee] = $this->repo->resolveByRekognitionId($rekognitionId);

        if (!$employeeId && !$name) {
            return new EmployeeMatch(false, 'No mapping found', null, null, $similarity, $confidence);
        }

        return new EmployeeMatch(true, $name, $employeeId, $employee, $similarity, $confidence);
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
            // بإمكانك إضافة Metadata عند الحاجة
            //'Metadata'    => ['source' => 'identifyEmployee'],
        ]);

        Log::info('S3 Upload Info', [
            'file' => $path,
            'mime' => $file->getMimeType(),
        ]);

        return $path;
    }
}
