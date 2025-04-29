<?php

namespace App\Services\Ocr;


use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\AnnotateImageRequest;
use Google\Cloud\Vision\V1\BatchAnnotateImagesRequest;
use Google\Cloud\Vision\V1\Client\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;

class GoogleVisionService
{
    protected $client;

    public function __construct()
    {
        // تحميل ملف الاعتماد من المسار
        $keyFilePath = storage_path('app/public/google_vision/info.json');
        // تهيئة العميل مع الاعتماد
        $this->client = new ImageAnnotatorClient([
            'credentials' => $keyFilePath,
        ]);
    }

    /**
     * استخراج النص من صورة (OCR)
     */


    public function detectText($imagePath)
    {

        if (!file_exists($imagePath)) {
            throw new \Exception("File not found: $imagePath");
        }

        // إعداد الصورة
        $image = (new \Google\Cloud\Vision\V1\Image())
            ->setContent(file_get_contents($imagePath));

        // إعداد نوع التحليل
        $feature = new \Google\Cloud\Vision\V1\Feature();
        $feature->setType(Type::TEXT_DETECTION);

        // إعداد طلب فردي
        $request = new AnnotateImageRequest();
        $request->setImage($image);
        $request->setFeatures([$feature]);

        // إنشاء الطلب العام batch
        $batchRequest = new BatchAnnotateImagesRequest();
        $batchRequest->setRequests([$request]);

        // تنفيذ الطلب
        $response = $this->client->batchAnnotateImages($batchRequest);
        $annotation = $response->getResponses()[0];

        if ($annotation->hasError()) {
            throw new \Exception($annotation->getError()->getMessage());
        }

        return $annotation->getTextAnnotations()[0]->getDescription();
    }
}
