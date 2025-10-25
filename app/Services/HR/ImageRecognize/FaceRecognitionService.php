<?php

namespace App\Services\HR\ImageRecognize;

use App\DTOs\HR\ImageRecognize\EmployeeMatch;
use App\Models\AttendanceImagesUploaded;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Repositories\HR\ImageRecognize\EmployeeRecognitionRepository;

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


        $matches = $result['FaceMatches'] ?? [];
        // dd($matches);   
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

        if (!$employee) {
            return EmployeeMatch::notFound();
        }
        // dd($name,$employee,$employeeId,$similarity,$confidence);
        if (!$employeeId && !$name) {
            return new EmployeeMatch(false, 'No mapping found', null, null, $similarity, $confidence);
        }

        // ✅ 5) إنشاء سجل attendance_images_uploaded
        // try {
        //     AttendanceImagesUploaded::create([
        //         'img_url'     => $path,
        //         'employee_id' => $employeeId,
        //         'datetime'    => now(),
        //     ]);
        // } catch (\Throwable $e) {
        //     Log::error('Failed to save uploaded attendance image', [
        //         'error' => $e->getMessage(),
        //         'path'  => $path,
        //     ]);
        // }


        return new EmployeeMatch(true, $name, $employeeId, $employee, $similarity, $confidence);
    }

    protected function uploadToS3(UploadedFile $file): string
    {
        $prefix   = trim($this->config['upload_prefix'] ?? 'uploads', '/');
        $ext      = $file->getClientOriginalExtension();
        $timestamp = now()->format('Ymd_His_u');
        $path     = "{$prefix}/identify_face_{$timestamp}.{$ext}";

        Storage::disk('s3')->put($path, fopen($file->getRealPath(), 'r'), [
            'visibility'  => $this->config['visibility'] ?? 'private',
            'ContentType' => $file->getMimeType(),
            // بإمكانك إضافة Metadata عند الحاجة
            //'Metadata'    => ['source' => 'identifyEmployee'],
        ]);



        return $path;
    }
}
