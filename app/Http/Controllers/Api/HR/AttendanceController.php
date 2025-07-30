<?php
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use Carbon\Carbon;
use App\Services\HR\Attendance\AttendanceService;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    protected $attendanceFetcher;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid'      => 'required|string|max:255',
            'date_time' => 'nullable|date',
        ]);

        $result = $this->attendanceService->handle($validated);

        return response()->json([
            'status'  => $result['success'] ? true : false,
            'message' => $result['message'],
            'data'    => $result['data'] ?? '',
        ], $result['success'] ? 200 : 422);
    }

    public function identifyEmployeeFromImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:jpg,jpeg,png|max:5120', // 5MB max
        ]);

        try {
            $file = $request->file('image');

            // ✅ تحقق من صحة الملف
            if (! $file->isValid()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Uploaded file is not valid.',
                ], 400);
            }

            // ✅ إعداد الاسم والمسار
            $fileName = 'identify_face_' . time() . '.' . $file->getClientOriginalExtension();
            $path     = "uploads/{$fileName}";

            // ✅ رفع الملف باستخدام fopen
            Storage::disk('s3')->put($path, fopen($file->getRealPath(), 'r'), [
                'visibility'  => 'private',
                'ContentType' => $file->getMimeType(),
            ]);

            // ✅ التحقق من صحة الرفع
            $sizeInBytes = Storage::disk('s3')->size($path);
            $mimeType    = $file->getMimeType();

            Log::info('S3 Upload Info', [
                'file'          => $fileName,
                'mime'          => $mimeType,
                's3_size_bytes' => $sizeInBytes,
            ]);

            if ($sizeInBytes === 0) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'File uploaded to S3 but is empty.',
                ], 500);
            }

            // ✅ تهيئة Rekognition
            $rekognitionClient = new RekognitionClient([
                'region'      => env('AWS_DEFAULT_REGION'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                ],
            ]);

            // ✅ استدعاء البحث عن الوجه
            $result = $rekognitionClient->searchFacesByImage([
                'CollectionId'       => 'workbenchemps2',
                'Image'              => [
                    'S3Object' => [
                        'Bucket' => env('AWS_BUCKET'),
                        'Name'   => $path,
                    ],
                ],
                'FaceMatchThreshold' => 90,
                'MaxFaces'           => 1,
            ]);

            // ✅ المعالجة الافتراضية
            $employeeData = [
                'found'         => false,
                'name'          => 'No match found',
                'employee_id'   => null,
                'employee_data' => null,
            ];

            if (! empty($result['FaceMatches'])) {
                $rekognitionId = $result['FaceMatches'][0]['Face']['FaceId'];

                // ✅ ربط بـ DynamoDB
                $dynamoDbClient = new DynamoDbClient([
                    'region'      => env('AWS_DEFAULT_REGION'),
                    'version'     => 'latest',
                    'credentials' => [
                        'key'    => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                $dynamoResult = $dynamoDbClient->getItem([
                    'TableName' => 'workbenchemps_recognition',
                    'Key'       => [
                        'RekognitionId' => ['S' => $rekognitionId],
                    ],
                ]);

                if (! empty($dynamoResult['Item']['Name']['S'])) {
                    $nameRaw      = $dynamoResult['Item']['Name']['S'];
                    $parts        = explode('-', $nameRaw);
                    $employeeName = $parts[0] ?? 'Unknown';
                    $employeeId   = $parts[1] ?? null;

                    $employee = Employee::find($employeeId);

                    $employeeData = [
                        'found'         => true,
                        'name'          => $employeeName,
                        'employee_id'   => $employeeId,
                        'employee_data' => $employee,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'match'  => $employeeData,
            ]);
        } catch (\Exception $e) {
            Log::error("Face recognition failed", [
                'message' => $e->getMessage(),
                'file'    => $file->getClientOriginalName() ?? 'unknown',
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Recognition failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}