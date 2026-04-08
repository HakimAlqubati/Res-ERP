<?php

namespace App\Http\Controllers\Api\HR;

use Exception;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\HR\Attendance\AttendancePlanService;
use App\Services\HR\AttendanceHelpers\EmployeePeriodHistoryService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceFetcher;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateService;
use App\Services\HR\AttendanceHelpers\Reports\EmployeesAttendanceOnDateServiceV2;
use App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesService;
use App\Services\HR\AttendanceHelpers\Reports\PresentEmployeesService;
use App\Services\HR\AttendanceHelpers\Reports\MissingCheckoutService;
use App\Services\HR\AttendanceHelpers\Reports\AttendanceImagesReportService;
use App\Services\HR\BranchAttendanceSummaryService;
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
    protected EmployeesAttendanceOnDateServiceV2 $employeesAttendanceOnDateServiceV2;
    protected AbsentEmployeesService $absentEmployeesService;
    protected PresentEmployeesService $presentEmployeesService;
    protected MissingCheckoutService $missingCheckoutService;

    public function __construct(
        AttendanceServiceV2 $attendanceService,
        EmployeesAttendanceOnDateService $employeesAttendanceOnDateService,
        EmployeesAttendanceOnDateServiceV2 $employeesAttendanceOnDateServiceV2,
        AbsentEmployeesService $absentEmployeesService,
        PresentEmployeesService $presentEmployeesService,
        MissingCheckoutService $missingCheckoutService
    ) {
        $this->attendanceService                   = $attendanceService;
        $this->attendanceFetcher                   = new AttendanceFetcher(new EmployeePeriodHistoryService());
        $this->employeesAttendanceOnDateService    = $employeesAttendanceOnDateService;
        $this->employeesAttendanceOnDateServiceV2  = $employeesAttendanceOnDateServiceV2;
        $this->absentEmployeesService              = $absentEmployeesService;
        $this->presentEmployeesService             = $presentEmployeesService;
        $this->missingCheckoutService              = $missingCheckoutService;
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

    /**
     * Get detailed multiple attendance breakdown for a specific employee, period, and date.
     *
     * GET /api/hr/multipleAttendanceDetails?employee_id=13&period_id=1&date=2026-02-15
     */
    public function multipleAttendanceDetails(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|integer|exists:hr_employees,id',
                'period_id'   => 'required|integer',
                'date'        => 'required|date',
            ]);

            $employeeId = $request->input('employee_id');
            $periodId   = $request->input('period_id');
            $date       = $request->input('date');

            // Fetch raw attendance records using AttendanceFetcher
            $attendances = $this->attendanceFetcher->getEmployeePeriodAttendnaceDetails($employeeId, $periodId, $date);

            // Use the calculator service
            $result = \App\Services\HR\AttendanceHelpers\Reports\AttendanceDetailsCalculator::calculateDetailedBreakdown(
                $attendances->toArray()
            );

            return response()->json([
                'status'             => 'success',
                'date'               => $date,
                'employee_id'        => $employeeId,
                'period_id'          => $periodId,
                'attendances'        => $result['attendances'],
                'total_hours'        => $result['total_hours'],
                'remaining_minutes'  => $result['remaining_minutes'],
                'total_minutes'      => $result['total_minutes'],
                'formatted_total'    => $result['total_hours'] . 'h ' . $result['remaining_minutes'] . 'm',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GET /api/hr/employeesAttendanceOnDate
     *
     * يقبل الفلترة بطريقتين:
     *   أ) ?employee_ids[]=1&employee_ids[]=2&date=2026-03-11  (الطريقة القديمة)
     *   ب) ?branch_id=9&date=2026-03-11                        (الطريقة الجديدة، أسرع)
     *
     * يستخدم ServiceV2 الذي يُقلِّل الاستعلامات إلى 6 بدلاً من 750+
     */
    public function employeesAttendanceOnDate(Request $request)
    {
        $validated = $request->validate([
            'employee_id'  => 'required_without_all:branch_id,employee_ids|integer',
            'employee_ids' => 'required_without_all:branch_id,employee_id|array',
            'branch_id'    => 'required_without_all:employee_ids,employee_id|integer|exists:branches,id',
            'date'         => 'required|date',
        ]);

        $date = $validated['date'];

        // تحديد معرّفات الموظفين بحسب الأولوية: موظف واحد، فرع، مجموعة موظفين
        $employeeIds = [];
        if (!empty($validated['employee_id'])) {
            $employeeIds = [$validated['employee_id']];
        } elseif (!empty($validated['branch_id'])) {
            $employeeIds = Employee::where('branch_id', $validated['branch_id'])
                ->where('active', 1)
                ->pluck('id')
                ->toArray();
        } elseif (!empty($validated['employee_ids'])) {
            $employeeIds = $validated['employee_ids'];
        }

        if (empty($employeeIds)) {
            return response()->json([
                'status' => 'success',
                'data'   => [],
                'count'  => 0,
            ]);
        }

        // استخدام Service V2 المُحسَّن (6 استعلامات فقط بدلاً من N×11)
        $attendanceReports = $this->employeesAttendanceOnDateServiceV2->fetchAttendances($employeeIds, $date);

        return response()->json([
            'status' => 'success',
            'count'  => $attendanceReports->count(),
            'date'   => $date,
            'data'   => $attendanceReports,
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

    public function absentEmployeesV2(Request $request, \App\Services\HR\AttendanceHelpers\Reports\AbsentEmployeesV2Service $service)
    {
        $validated = $request->validate([
            'date'          => 'sometimes|required|date',
            'from_date'     => 'required_without:date|date',
            'to_date'       => 'required_without:date|date|after_or_equal:from_date',
            'branch_id'     => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $dateFrom = $request->input('from_date');
        $dateTo   = $request->input('to_date');

        if (!$dateFrom && !$dateTo && $request->has('date')) {
            $dateFrom = $request->input('date');
            $dateTo   = $request->input('date');
        }

        $filters  = array_filter($request->only(['branch_id', 'department_id']));

        $records = $service->getAbsentEmployees($dateFrom, $dateTo, $filters);

        return response()->json([
            'status'    => 'success',
            'date_from' => Carbon::parse($dateFrom)->toDateString(),
            'date_to'   => Carbon::parse($dateTo)->toDateString(),
            'message'   => 'Absent employees.',
            'count'     => $records->count(),
            'data'      => $records, // This will return grouped by employee
        ]);
    }

    /**
     * GET /api/hr/presentEmployees
     *
     * إرجاع الموظفين الحاضرين حالياً بناءً على:
     *   - وجود بصمة دخول مقبولة في اليوم المحدد.
     *   - لم يُسجَّل لهم خروج بعد.
     *   - الوقت الحالي يقع داخل نافذة الوردية الممتدة
     *     [start_at - allowedHoursBefore]  ←→  [end_at + allowedHoursAfter]
     *
     * Query Params:
     *   - datetime      : Y-m-d H:i:s  (اختياري، افتراضي = now)
     *   - branch_id     : integer       (اختياري)
     *   - department_id : integer       (اختياري)
     */
    public function presentEmployees(Request $request)
    {
        $validated = $request->validate([
            'datetime'      => 'nullable|date',
            'branch_id'     => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $datetime = isset($validated['datetime'])
            ? Carbon::parse($validated['datetime'])
            : Carbon::now();

        $filters = array_filter($request->only(['branch_id', 'department_id']));

        return $this->presentEmployeesService->getReport($datetime, $filters)->toResponse();
    }

    /**
     * GET /api/hr/missingCheckout
     *
     * Returns employees who have an accepted check-in for the given date
     * but have NOT recorded an accepted check-out yet.
     *
     * Query Params:
     *   - date          : Y-m-d   (optional, defaults to today)
     *   - branch_id     : integer (optional)
     *   - department_id : integer (optional)
     */
    public function missingCheckout(Request $request)
    {
        // For backwards compatibility and convenience, if they only pass 'date', use it for both.
        // Otherwise require from_date and to_date.
        $validated = $request->validate([
            'date'          => 'sometimes|required|date',
            'from_date'     => 'required_without:date|date',
            'to_date'       => 'required_without:date|date|after_or_equal:from_date',
            'branch_id'     => 'required|integer',
            'department_id' => 'nullable|integer',
        ]);

        $dateFrom = $request->input('from_date');
        $dateTo   = $request->input('to_date');

        // Fallback for missing 'date'
        if (!$dateFrom && !$dateTo && $request->has('date')) {
            $dateFrom = $request->input('date');
            $dateTo   = $request->input('date');
        }

        $filters  = array_filter($request->only(['branch_id', 'department_id']));

        $records = $this->missingCheckoutService->getMissingCheckouts($dateFrom, $dateTo, $filters);

        return response()->json([
            'status'  => 'success',
            'date_from' => Carbon::parse($dateFrom)->toDateString(),
            'date_to'   => Carbon::parse($dateTo)->toDateString(),
            'message' => 'Employees missing check-out.',
            'count'   => $records->count(),
            'data'    => $records,
        ]);
    }

    /**
     * GET /api/v2/hr/missingCheckout
     * Same as above but groups the 'data' array by 'checkin_date'.
     */
    public function missingCheckoutV2(Request $request)
    {
        $validated = $request->validate([
            'date'          => 'sometimes|required|date',
            'from_date'     => 'required_without:date|date',
            'to_date'       => 'required_without:date|date|after_or_equal:from_date',
            'branch_id'     => 'nullable|integer',
            'department_id' => 'nullable|integer',
        ]);

        $dateFrom = $request->input('from_date');
        $dateTo   = $request->input('to_date');

        if (!$dateFrom && !$dateTo && $request->has('date')) {
            $dateFrom = $request->input('date');
            $dateTo   = $request->input('date');
        }

        $filters  = array_filter($request->only(['branch_id', 'department_id']));

        $records = $this->missingCheckoutService->getMissingCheckouts($dateFrom, $dateTo, $filters);

        // Uses a resource collection to format the data
        return response()->json([
            'status'    => 'success',
            'date_from' => Carbon::parse($dateFrom)->toDateString(),
            'date_to'   => Carbon::parse($dateTo)->toDateString(),
            'message'   => 'Employees missing check-out.',
            'count'     => $records->count(),
            'data'      => new \App\Http\Resources\HR\MissingCheckoutGroupCollection($records),
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

    /**
     * صور الحضور التي لها سجل حضور مقبول (accepted)
     */
    public function attendanceImages(Request $request)
    {
        try {
            $query = \App\Models\AttendanceImagesUploaded::query()
                ->select('attendance_images_uploaded.*')
                ->join('hr_attendances', function ($join) {
                    $join->on('hr_attendances.source_id', '=', 'attendance_images_uploaded.id')
                        ->where('hr_attendances.source_type', '=', \App\Models\AttendanceImagesUploaded::class)
                        ->where('hr_attendances.accepted', 1);
                })
                ->with(['employee:id,name,branch_id', 'attendances' => function ($q) {
                    $q->where('accepted', 1)
                        ->select('id', 'source_type', 'source_id', 'check_type', 'status', 'check_date', 'check_time', 'employee_id', 'period_id')
                        ->with('period:id,name');
                }]);
            // ->whereHas('attendances', function ($q) {
            //     $q->where('accepted', 1);
            // });

            // فلتر بالموظف
            if ($request->filled('employee_id')) {
                $query->where('attendance_images_uploaded.employee_id', $request->input('employee_id'));
            }

            // فلتر بالتاريخ
            if ($request->filled('from_date')) {
                $query->whereDate('hr_attendances.check_date', '>=', $request->input('from_date'));
            }
            if ($request->filled('to_date')) {
                $query->whereDate('hr_attendances.check_date', '<=', $request->input('to_date'));
            }

            // فلتر بالفرع
            if ($request->filled('branch_id')) {
                $query->whereHas('employee', function ($q) use ($request) {
                    $q->where('branch_id', $request->input('branch_id'));
                });
            }

            $perPage = $request->input('per_page', 20);

            // User requested chronological order which honors multi-day shifts
            // We sort by check_date DESC (newest day first)
            // then by employee_id ASC
            // then by the actual physical datetimeASC so CheckIn always appears before CheckOut for that day
            $images = $query->orderBy('hr_attendances.check_date', 'desc')
                ->orderBy('attendance_images_uploaded.employee_id')

                // ->orderBy('attendance_images_uploaded.datetime', 'asc')
                ->paginate($perPage);

            // تحويل البيانات مع إضافة labels و colors
            $mappedImages = $images->getCollection()->map(function ($image) {
                $attendance = $image->attendances->first();

                // Filter Logic: Only return First CheckIn and Last CheckOut per (employee, date, period)
                if ($attendance) {
                    $keep = false;

                    if ($attendance->check_type == \App\Models\Attendance::CHECKTYPE_CHECKIN) {
                        // Check if there is any strictly earlier checkin
                        $earlierExists = \App\Models\Attendance::where('employee_id', $attendance->employee_id)
                            ->where('check_date', $attendance->check_date)
                            ->where('period_id', $attendance->period_id)
                            ->where('check_type', \App\Models\Attendance::CHECKTYPE_CHECKIN)
                            ->where('accepted', 1)
                            ->where('id', '<', $attendance->id)
                            ->exists();

                        if (!$earlierExists) {
                            $keep = true;
                        }
                    } elseif ($attendance->check_type == \App\Models\Attendance::CHECKTYPE_CHECKOUT) {
                        // Check if there is any strictly later checkout
                        $laterExists = \App\Models\Attendance::where('employee_id', $attendance->employee_id)
                            ->where('check_date', $attendance->check_date)
                            ->where('period_id', $attendance->period_id)
                            ->where('check_type', \App\Models\Attendance::CHECKTYPE_CHECKOUT)
                            ->where('accepted', 1)
                            ->where('id', '>', $attendance->id)
                            ->exists();

                        if (!$laterExists) {
                            $keep = true;
                        }
                    }

                    // If filter failed, return null to remove from list
                    if (!$keep) {
                        return null;
                    }
                }

                return [
                    'id'             => $image->id,
                    'img_url'        => $image->full_image_url,
                    'employee_id'    => $image->employee_id,
                    'employee_name'  => $image->employee?->name ?? 'Unknown',
                    'datetime'       => $image->datetime,
                    'attendance'     => $attendance ? [
                        'id'              => $attendance->id,
                        'check_type'      => $attendance->check_type,
                        'check_type_label' => \App\Models\Attendance::getCheckTypes()[$attendance->check_type] ?? $attendance->check_type,
                        'status'          => $attendance->status,
                        'status_label'    => \App\Models\Attendance::getStatusLabel($attendance->status),
                        'status_color'    => \App\Models\Attendance::getStatusColor($attendance->status),
                        'status_hex'      => \App\Models\Attendance::getStatusHex($attendance->status),
                        'check_date'      => $attendance->check_date,
                        'check_time'      => $attendance->check_time,
                        'period_id'       => $attendance->period_id,
                        'period_name'     => $attendance->period ? $attendance->period->name : null,
                    ] : null,
                ];
            })->filter()->values(); // Filter out nulls and reindex

            $images->setCollection($mappedImages);

            return response()->json([
                'status' => 'success',
                'data'   => $images,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * صور الحضور الإصدار الثاني باستخدام كلاس خدمة
     */
    public function attendanceImagesV2(Request $request, AttendanceImagesReportService $reportService)
    {
        try {
            $reportService->includeRequests = $request->boolean('include_requests', true);
            $images = $reportService->getImagesReport($request);

            return response()->json([
                'status' => 'success',
                'data'   => $images,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Branch Attendance Summary Report
     *
     * GET /api/hr/branchAttendanceSummary?branch_id=7&year=2026&month=2
     */
    public function branchAttendanceSummary(Request $request, BranchAttendanceSummaryService $summaryService)
    {
        try {
            $validated = $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'year'      => 'required|integer|min:2000',
                'month'     => 'required|integer|between:1,12',
            ]);

            $report = $summaryService->generate(
                $validated['branch_id'],
                $validated['year'],
                $validated['month']
            );

            return response()->json([
                'status' => 'success',
                'data'   => $report,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
