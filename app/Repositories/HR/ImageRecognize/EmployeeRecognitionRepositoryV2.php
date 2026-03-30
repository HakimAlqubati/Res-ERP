<?php

namespace App\Repositories\HR\ImageRecognize;

use Spatie\Multitenancy\Models\Tenant;
use App\Models\Employee;
use App\Models\AppLog;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Support\Facades\Log;

class EmployeeRecognitionRepositoryV2
{
    public function __construct(
        protected DynamoDbClient $dynamo,
        protected string $table = 'face_recognition',
    ) {}

    /**
     * يعيد [name, employeeId, employee, isAnotherBranch] إن وُجدت
     */
    public function resolveByRekognitionId(string $rekognitionId): array
    {
        $result = $this->dynamo->getItem([
            'TableName' => $this->table,
            'Key'       => [
                'RekognitionId' => ['S' => $rekognitionId],
            ],
            'ConsistentRead' => true,
        ]);

        // ✅ تسجيل الجدول المستخدم
        AppLog::write(
            'DynamoDB Lookup Config',
            AppLog::LEVEL_INFO,
            'FaceRecognition',
            [
                'dynamodb_table'  => $this->table,
                'rekognition_id'  => $rekognitionId,
                'item_found'      => isset($result['Item']),
                'raw_item'        => $result['Item'] ?? null,
            ]
        );

        $item = $result['Item'] ?? null;
        // Log::info('item', [$item]);
        if (!$item || empty($item['Name']['S'])) {
            return [null, null, null, false];
        }

        // ✅ التحقق من أن الموظف ينتمي لنفس المشروع (Tenant) عبر الـ baseUrl
        $itemBaseUrl   = $item['baseUrl']['S'] ?? null;
        $currentTenant = Tenant::current();

        // تحديد الرابط الحالي (إما من التينانت النشط أو من إعدادات التطبيق)
        $currentAppUrl = ($currentTenant && isset($currentTenant->domain))
            ? $currentTenant->domain
            : config('app.url');

        // تنظيف الروابط من http/https و السلاش الأخير للمقارنة الدقيقة
        $normalize = function ($url) {
            return rtrim(preg_replace('/^https?:\/\//', '', (string)$url), '/');
        };

        // Log::info('baseUrl',[$itemBaseUrl,$currentAppUrl]);
        if ($itemBaseUrl && $normalize($itemBaseUrl) !== $normalize($currentAppUrl)) {
            return [null, null, null, false];
        }

        $nameRaw = $item['Name']['S']; // مثال: "Ali-123"
        // dd($item);
        $parts  = explode('-', $nameRaw);
        $empId  = trim(array_pop($parts));          // آخر جزء = ID
        $name   = trim(implode('-', $parts));

        $currentBranchId = auth()->user()?->branch_id;

        // if (!$currentBranchId) {
        //     abort(403, 'Branch context is required to resolve employee.');
        // }

        // إن لم يتوفر رقم موظف صالح نرجع الاسم والـ ID كما هو وبدون موديل
        if (!$empId) {
            return [$name, null, null, false];
        }

        // 1) البحث عن الموظف في كل الفروع للتأكد من وجوده
        $globalEmployee = Employee::find($empId);

        // Log::info('employee_id', [$empId, $empId, $name, $currentBranchId, $globalEmployee]);
        if (!$globalEmployee) {
            return [null, null, null, false];
        }

        // 2) التحقق من الفرع
        if ($globalEmployee->branch_id != $currentBranchId) {
            return [$name, $empId, null, true]; // وجدنا الموظف لكنه في فرع آخر
        }

        return [$name, $empId, $globalEmployee, false];
    }
}
