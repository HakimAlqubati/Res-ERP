<?php

namespace App\Http\Controllers\Api\HR;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\Attendance\AttendancePlanService;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesService;
use App\Services\HR\v2\Attendance\AttendanceServiceV2;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    protected AttendanceServiceV2 $attendanceService;
    protected $attendanceFetcher;
    protected EmployeesAttendanceOnDateService $employeesAttendanceOnDateService;
    protected AbsentEmployeesService $absentEmployeesService;

    public function __construct(
        AttendanceServiceV2 $attendanceService,
        EmployeesAttendanceOnDateService $employeesAttendanceOnDateService,
        AbsentEmployeesService $absentEmployeesService
    ) {
        $this->attendanceService = $attendanceService;
        $this->attendanceFetcher = new AttendanceFetcher(new EmployeePeriodHistoryService());
        $this->employeesAttendanceOnDateService = $employeesAttendanceOnDateService;
        $this->absentEmployeesService = $absentEmployeesService;
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rfid'        => 'nullable|string|max:255',
            'employee_id' => 'nullable|integer|exists:hr_employees,id',
            'date_time'   => 'nullable|date',
            'type'        => 'nullable|string|in:checkin,checkout',
            'attendance_type' => 'nullable|string|in:rfid,request,webcam',
        ]);

        // لازم واحد منهم يكون موجود
        if (empty($validated['rfid']) && empty($validated['employee_id'])) {
            return response()->json([
                'success'  => false,
                'message' => 'Either rfid or employee_id is required.',
            ], 422);
        }

        $result = $this->attendanceService->handle($validated);

        return response()->json([
            'success'  => $result['success'],
            'status'   => $result['success'],  // للتوافق مع الـ Frontend القديم
            'type_required' => $result['type_required'] ?? false,
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

    public function absentEmployees(Request $request)
    {
        $validated = $request->validate([
            'date'          => 'required|date',
            'branch_id'     => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $filters = array_filter($request->only(['branch_id', 'department_id']));

        $absents = $this->absentEmployeesService->getAbsentEmployees($validated['date'], $filters);

        return response()->json([
            'status' => 'success',
            'count'  => $absents->count(),
            'data'   => $absents
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
                'CollectionId'       => 'emps',
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
                    'TableName' => 'face_recognition',
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

    public function generate(Request $request, AttendancePlanService $service)
    {
        $validated = $request->validate([
            'work_period_id' => 'required|integer|exists:hr_work_periods,id',
            'from_date'      => 'required|date',
            'to_date'        => 'required|date|after_or_equal:from_date',
        ]);

        $plan = $service->buildPlan(
            $validated['work_period_id'],
            $validated['from_date'],
            $validated['to_date']
        );

        return response()->json($plan);
    }
}
