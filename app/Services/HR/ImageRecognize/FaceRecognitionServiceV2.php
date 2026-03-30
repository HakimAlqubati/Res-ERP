<?php

namespace App\Services\HR\ImageRecognize;

use App\DTOs\HR\ImageRecognize\EmployeeMatch;
use App\Models\AppLog;
use App\Models\AttendanceImagesUploaded;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Repositories\HR\ImageRecognize\EmployeeRecognitionRepositoryV2;

class FaceRecognitionServiceV2
{
    public function __construct(
        protected RekognitionClient $rekognition,
        protected EmployeeRecognitionRepositoryV2 $repo,
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

        // ✅ تسجيل معلومات البحث
        AppLog::write(
            'Face Recognition Search',
            AppLog::LEVEL_INFO,
            'FaceRecognition',
            [
                'collection_id'        => $collectionId,
                'bucket'               => $bucket,
                'image_path'           => $path,
                'face_match_threshold' => $this->config['face_match_threshold'],
                'max_faces'            => $this->config['max_faces'],
                'matches_count'        => count($result['FaceMatches'] ?? []),
                'raw_matches'          => $result['FaceMatches'] ?? [],
            ]
        );

        $matches = $result['FaceMatches'] ?? [];
        // dd($matches);
        Log::info('AWS Rekognition Matches', ['matches' => $matches]);
        // dd($matches);   
        if (empty($matches)) {
            return EmployeeMatch::notFound();
        }

        // البحث عن أفضل تطابق ينتمي لهذا المشروع والفرع
        $matchedResult  = null;
        $fallbackResult = null;

        foreach ($matches as $match) {
            $rekognitionId = $match['Face']['FaceId'] ?? null;
            $similarity    = isset($match['Similarity']) ? (float) $match['Similarity'] : null;
            $confidence    = isset($match['Face']['Confidence']) ? (float) $match['Face']['Confidence'] : null;

            if (!$rekognitionId) continue;

            // 4) محاولة حل الهوية لربطها بالموظف (مع التحقق من المشروع والفرع)
            [$name, $employeeId, $employee, $isAnotherBranch] = $this->repo->resolveByRekognitionId($rekognitionId);

            // ✅ حالة النجاح: الموظف موجود في نفس الفرع
            if ($employee && !$isAnotherBranch) {
                $matchedResult = new EmployeeMatch(true, $name, $employeeId, $employee, $similarity, $confidence);
                break; // نخرج فوراً عند العثور على التطابق الصحيح
            }

            // ⚠️ حالة بديلة: الموظف موجود في هذا المشروع ولكن بفرع آخر
            if ($isAnotherBranch && !$fallbackResult) {
                $fallbackResult = new EmployeeMatch(false, $name, $employeeId, null, $similarity, $confidence, 'Access denied: Employee belongs to a different branch.');
            }
        }

        // النتيجة النهائية المفضلة هي التطابق المحلي، ثم التطابق في فرع آخر
        $finalMatchResult = $matchedResult ?: $fallbackResult;

        if (!$finalMatchResult) {
            return EmployeeMatch::notFound('This employee is removed from AWS Rekognition or belongs to another project.');
        }

        // في حالة الموظف من فرع آخر، نرجعه بـ found = false كما هو مخزن في الـ fallback
        if ($finalMatchResult === $fallbackResult) {
            return $finalMatchResult;
        }

        // استخراج البيانات للخطوات القادمة
        $name       = $finalMatchResult->name;
        $employeeId = $finalMatchResult->employeeId;
        $employee   = $finalMatchResult->employeeData;

        if (!$employeeId && !$name) {
            return new EmployeeMatch(false, 'Unidentified face', null, null, $similarity, $confidence, 'Face not linked to any employee.');
        }

        // ✅ 5) إنشاء سجل attendance_images_uploaded
        try {
            AttendanceImagesUploaded::create([
                'img_url'     => $path,
                'employee_id' => $employeeId,
                'datetime'    => now(),
            ]);
        } catch (\Throwable $e) {
            // AppLog::write(
            //     'Failed to save uploaded attendance image',
            //     AppLog::LEVEL_ERROR,
            //     'FaceRecognition',
            //     [
            //         'error' => $e->getMessage(),
            //         'path'  => $path,
            //     ]
            // );
        }


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
