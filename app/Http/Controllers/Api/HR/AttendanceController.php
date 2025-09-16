<?php

namespace App\Http\Controllers\Api\HR;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use App\Services\HR\Attendance\AttendanceService;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;
    protected $attendanceFetcher;
    protected EmployeesAttendanceOnDateService $employeesAttendanceOnDateService;

    public function __construct(AttendanceService $attendanceService, EmployeesAttendanceOnDateService $employeesAttendanceOnDateService)
    {
        $this->attendanceService = $attendanceService;
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
        $this->employeesAttendanceOnDateService = $employeesAttendanceOnDateService;
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid'        => 'nullable|string|max:255',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'date_time'   => 'nullable|date',
            'type'        => 'nullable|string|in:checkin,checkout',
        ]);

        // لازم واحد منهم يكون موجود
        if (empty($validated['rfid']) && empty($validated['employee_id'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Either rfid or employee_id is required.',
            ], 422);
        }

        $result = $this->attendanceService->handle($validated);

        return response()->json([
            'status'  => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? '',
        ], $result['success'] ? 200 : 422);
    }


    public function storeInOut(Request $request)
    {
        $validated = $request->validate([
            'rfid'        => 'nullable|string|max:255',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'check_in'    => 'nullable|date',
            'check_out'   => 'nullable|date|after:check_in',
        ]);

        // لازم واحد منهم يكون موجود
        if (empty($validated['rfid']) && empty($validated['employee_id'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Either rfid or employee_id is required.',
            ], 422);
        }

        $result = $this->attendanceService->handleTwoDates($validated);

        return response()->json([
            'status'  => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? '',
        ], $result['success'] ? 200 : 422);
    }

    public function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'rfid'          => 'nullable|string|max:255',
            'employee_id'   => 'nullable|integer|exists:hr_employees,id',
            'check_in'      => 'nullable|date',
            'check_out'     => 'nullable|date|after:check_in',
            'from_date'     => 'nullable|date',
            'to_date'       => 'nullable|date|after_or_equal:from_date',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s|after:check_in_time',
        ]);

        if (empty($validated['rfid']) && empty($validated['employee_id'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Either rfid or employee_id is required.',
            ], 422);
        }

        $result = $this->attendanceService->handleBulk($validated);

        return response()->json([
            'status'  => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? '',
        ], $result['success'] ? 200 : 422);
    }


    public function employeeAttendance(Request $request)
    {
        try {
            $employee_id = $request->input('employee_id');

            if (! $employee_id) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Employee ID is required.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $employee = Employee::find($employee_id);

            if (! $employee) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Employee not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // تحديد النطاق الزمني
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate   = Carbon::parse($request->input('end_date'));

            // إظهار الحقول الإضافية
            $showDay = $request->input('show_day', false);

            // جلب بيانات الحضور
            $data = $this->attendanceFetcher->fetchEmployeeAttendances($employee, $startDate, $endDate);

            // قيم افتراضية
            $totalSupposed = '0 h 0 m';
            $totalWorked   = 0;
            $totalApproved = 0;

            return response()->json([
                'status'                      => 'success',
                'report_data'                 => $data,
                'show_day'                    => $showDay,
                'employee_id'                 => $employee_id,
                'start_date'                  => $startDate->format('Y-m-d'),
                'end_date'                    => $endDate->format('Y-m-d'),
                'totalSupposed'               => $totalSupposed,
                'totalWorked'                 => $this->formatDuration($totalWorked),
                'totalApproved'               => $this->formatDuration($totalApproved),
                'total_actual_duration_hours' => $data['total_actual_duration_hours'] ?? 0,
                'total_duration_hours'        => $data['total_duration_hours'] ?? 0,
                'total_approved_overtime'     => $data['total_approved_overtime'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesAttendanceOnDate(Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'date'         => 'required|date',
        ]);

        $employeeIds = $validated['employee_ids'];
        $date = $validated['date'];

        $attendanceReports = $this->employeesAttendanceOnDateService->fetchAttendances($employeeIds, $date);

        return response()->json([
            'status'  => 'success',
            'data'    => $attendanceReports,
        ]);
    }

    private function formatDuration($totalMinutes)
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return "{$hours} h {$minutes} m";
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

            Log::info('rekognition_result', [$result]);
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

                Log::info('dynamo_result', [$dynamoResult]);
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
        } catch (Exception $e) {
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
