<?php

return [
    'region'               => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket'               => env('AWS_BUCKET'),
    'collection_id'        => env('REKOGNITION_COLLECTION', 'workbenchemps2'),
    'face_match_threshold' => (float) env('REKOGNITION_THRESHOLD', 90),
    'max_faces'            => (int) env('REKOGNITION_MAX_FACES', 1),

    // مسار رفع الصور داخل S3
    'upload_prefix'        => env('REKOGNITION_UPLOAD_PREFIX', 'uploads'),
    // اجعل الرفع Private افتراضياً
    'visibility'           => env('REKOGNITION_S3_VISIBILITY', 'private'),
];
