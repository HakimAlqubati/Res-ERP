<?php

namespace App\Http\Controllers\AWS;

use App\Http\Controllers\Controller;
use App\Models\LivenessSession;
use App\Services\HR\v2\Attendance\AttendanceServiceV2;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;

class EmployeeLivenessController extends Controller
{

    protected AttendanceServiceV2 $attendanceService;

    public function __construct(AttendanceServiceV2 $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }
    public function startSession()
    {
        $client = new RekognitionClient([
            'region'      => env('AWS_US_EAST_REGION'),
            'version'     => 'latest',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $result = $client->createFaceLivenessSession([
            'ClientRequestToken' => 'liveness_' . bin2hex(random_bytes(8)),

            'Settings'           => [
                'AuditImagesLimit'     => 2,
                'ChallengePreferences' => [
                    ['Type' => 'FaceMovementAndLightChallenge'],
                ],
            ],
        ]);

        return response()->json(['sessionId' => $result['SessionId']]);
    }

    public function checkSession(Request $request)
    {
        $sessionId = $request->input('sessionId');

        try {
            // 1. كشف الحيوية في us-east-1
            $livenessClient = new \Aws\Rekognition\RekognitionClient([
                'region'      => 'us-east-1',
                'version'     => 'latest',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            $result = $livenessClient->getFaceLivenessSessionResults([
                'SessionId' => $sessionId,
            ]);

            if (isset($result['Status']) && $result['Status'] === 'SUCCEEDED') {
                $imageBytes = $result['ReferenceImage']['Bytes'] ?? null;

                // if (! $imageBytes) {
                //     LivenessSession::createLivenessSession([
                //         'session_id' => $sessionId,
                //         'status'     => 'NO_IMAGE',
                //         'error'      => 'لم يتم العثور على صورة مرجعية.',
                //     ]);
                //     return response()->json([
                //         'status'  => 'NO_IMAGE',
                //         'message' => 'لم يتم العثور على صورة مرجعية.',
                //     ], 400);
                // }

                // 2. البحث عن الوجه في مجموعة Rekognition في سنغافورة
                $rekognitionClient = new \Aws\Rekognition\RekognitionClient([
                    'region'      => 'ap-southeast-1',
                    'version'     => 'latest',
                    'credentials' => [
                        'key'    => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                $searchResult = $rekognitionClient->searchFacesByImage([
                    'CollectionId'       => 'workbenchemps2', // يجب أن تكون المجموعة منشأة في سنغافورة
                    'Image'              => [
                        'Bytes' => $imageBytes,
                    ],
                    'FaceMatchThreshold' => 90,
                    'MaxFaces'           => 1,
                ]);

                $rekognitionId = null;
                $name          = 'No match found';

                // إذا كان هناك تطابق
                if (! empty($searchResult['FaceMatches'])) {
                    $rekognitionId = $searchResult['FaceMatches'][0]['Face']['FaceId'];

                    // 3. (اختياري) جلب اسم الموظف من DynamoDB
                    try {
                        $dynamoDbClient = new \Aws\DynamoDb\DynamoDbClient([
                            'region'      => 'ap-southeast-1', // نفس منطقة المجموعة
                            'version'     => 'latest',
                            'credentials' => [
                                'key'    => env('AWS_ACCESS_KEY_ID'),
                                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                            ],
                        ]);

                        $dynamoResult = $dynamoDbClient->getItem([
                            'TableName' => 'workbenchemps_recognition',
                            'Key'       => [
                                'RekognitionId' => [
                                    'S' => $rekognitionId,
                                ],
                            ],
                        ]);

                        if (! empty($dynamoResult['Item']['Name']['S'])) {
                            $name = $dynamoResult['Item']['Name']['S'];
                        }
                    } catch (\Exception $e) {
                        // لا مشكلة لو فشل DynamoDB، نواصل النتيجة مع FaceId فقط
                    }
                }

                // تقسيم الاسم لو كان بصيغة "الاسم-المعرف"
                $expodedResult = explode('-', $name);
                $employeeId    = $expodedResult[1] ?? 0;
                $employeeName  = $expodedResult[0] ?? 'Employee not found';

                $attendanceResult = null;
                if ($employeeId && is_numeric($employeeId) && $employeeId > 0) {
                    $attendanceResult = $this->attendanceService->handle([
                        'employee_id' => $employeeId,
                        // يمكنك إرسال بيانات إضافية مثل 'date_time' => now() إذا أردت
                    ], 'face'); // النوع face للتمييز إن أردت
                }

                LivenessSession::createLivenessSession([
                    'session_id'         => $sessionId,
                    // 'employee_id'        => $employeeId ?? null,
                    'employee_id'        => 0,
                    'employee_name'      => $employeeName ?? null,
                    'face_id'            => $rekognitionId ?? null,
                    'raw_name'           => $name ?? null,
                    'is_live'            => ($result['Confidence'] ?? 0) > 70,
                    'confidence'         => $result['Confidence'] ?? null,
                    'status'             => $result['Status'] ?? 'FAILED',
                    'audit_images_count' => count($result['AuditImages'] ?? []),
                    'attendance_result'  => $attendanceResult ?? null,
                    'error'              => null,
                ]);
                // 4. إرجاع النتيجة النهائية
                return response()->json([
                    'status'           => 'success',
                    'isLive'           => ($result['Confidence'] ?? 0) > 70,
                    'confidence'       => $result['Confidence'] ?? null,
                    'employee_id'      => $employeeId,
                    'employee'         => $employeeName,
                    'face_id'          => $rekognitionId,
                    'raw_name'         => $name,
                    'auditImagesCount' => count($result['AuditImages'] ?? []),
                    'attendance'       => $attendanceResult,
                ]);
            }

            // في حالة فشل التحقق الحيوي
            return response()->json([
                'status'  => $result['Status'] ?? 'FAILED',
                'isLive'  => false,
                'message' => 'فشل التحقق من الحيوية',
            ], 400);
        } catch (\Aws\Exception\AwsException $e) {
            // خطأ من AWS (مثلاً session غير صالح)
            return response()->json([
                'status' => 'FAILED',
                'error'  => $e->getAwsErrorMessage() ?: $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // أي خطأ آخر في النظام
            return response()->json([
                'status' => 'FAILED',
                'error'  => $e->getMessage(),
            ], 500);
        }
        return response()->json([
            'status' => 'FAILED',
            'error'  => 'no',
        ], 500);
    }

    // public function checkSession(Request $request)
    // {
    //     $sessionId = $request->input('sessionId');
    //     $client    = new RekognitionClient([
    //         'region'      => env('AWS_US_EAST_REGION'),
    //         'version'     => 'latest',
    //         'credentials' => [
    //             'key'    => env('AWS_ACCESS_KEY_ID'),
    //             'secret' => env('AWS_SECRET_ACCESS_KEY'),
    //         ],
    //     ]);

    //     $result = $client->getFaceLivenessSessionResults([
    //         'SessionId' => $sessionId,
    //     ]);

    //     if (isset($result['Status']) && $result['Status'] === 'SUCCEEDED') {
    //         // ٣. استخراج بيانات الصورة

    //         return response()->json([
    //             'status'              => $result['Status'] ?? 'UNKNOWN',
    //             'confidence'          => $result['Confidence'] ?? null,
    //             'isLive'              => $result['Confidence'] > 90,
    //             // 's3Path'              => $s3Path,
    //             // 's3Url'               => Storage::disk('s3')->url($s3Path),
    //             // 'referenceImageBytes' => base64_encode($result['ReferenceImage']['Bytes'] ?? ''),
    //             'auditImagesCount'    => count($result['AuditImages'] ?? []),
    //         ]);
    //     }

    //     // في حالة فشل التحقق
    //     return response()->json([
    //         'status' => $result['Status'] ?? 'FAILED',
    //         'isLive' => false,
    //     ], 400);
    // }
}
