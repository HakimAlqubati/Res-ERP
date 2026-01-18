<?php

namespace App\Repositories\HR\ImageRecognize;

use App\Models\AppLog;
use Aws\DynamoDb\DynamoDbClient;
use App\Models\Employee;

class EmployeeRecognitionRepository
{
    public function __construct(
        protected DynamoDbClient $dynamo,
        protected string $table = 'face_recognition',
    ) {}

    /**
     * يعيد [name, employeeId] إن وُجدت
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
        if (!$item || empty($item['Name']['S'])) {
            return [null, null, null];
        }

        $nameRaw = $item['Name']['S']; // مثال: "Ali-123"
        $parts  = explode('-', $nameRaw);
        $empId  = trim(array_pop($parts));          // آخر جزء = ID
        $name   = trim(implode('-', $parts));

        $currentBranchId = auth()->user()?->branch_id;

        if (!$currentBranchId) {
            abort(403, 'Branch context is required to resolve employee.');
            // أو يمكن: throw new AuthorizationException('Branch context is required.');
        }

        // إن لم يتوفر رقم موظف صالح نرجع الاسم والـ ID كما هو وبدون موديل
        if (!$empId) {
            return [$name, null, null];
        }

        $employee = Employee::query()
            ->where('branch_id', $currentBranchId)
            ->whereKey($empId)
            ->first();
        if (!$employee) {
            return [null, null, null];
        }
        return [$name, $empId, $employee];
    }
}
