<?php

return [
    'region'               => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket'               => env('AWS_BUCKET'),
    'collection_id'        => env('REKOGNITION_COLLECTION', 'emps'),
    'face_match_threshold' => (float) env('REKOGNITION_THRESHOLD', 90),
    'max_faces'            => (int) env('REKOGNITION_MAX_FACES', 1),

    // مسار رفع الصور داخل S3
    'upload_prefix'        => env('REKOGNITION_UPLOAD_PREFIX', 'uploads'),
    // اجعل الرفع Private افتراضياً
    'visibility'           => env('REKOGNITION_S3_VISIBILITY', 'private'),

        // 🔁 إعدادات إعادة المحاولة
    'max_retries'          => (int) env('REKOGNITION_MAX_RETRIES', 2),     // عدد مرات الإعادة الإضافية
    'retry_backoff_ms'     => (int) env('REKOGNITION_RETRY_BACKOFF_MS', 350),// التأخير الابتدائي بالمللي ثانية
    'retry_jitter_ms'      => (int) env('REKOGNITION_RETRY_JITTER_MS', 150), // عشوائية بسيطة
    'threshold_step'       => (float) env('REKOGNITION_THRESHOLD_STEP', 3),  // كم ننقص العتبة كل محاولة
    'min_threshold'        => (float) env('REKOGNITION_MIN_THRESHOLD', 75),  // أقل عتبة مسموحة

];
