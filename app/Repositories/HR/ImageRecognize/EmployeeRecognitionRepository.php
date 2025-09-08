<?php

namespace App\Repositories\HR\ImageRecognize;

use Aws\DynamoDb\DynamoDbClient;
use App\Models\Employee;

class EmployeeRecognitionRepository
{
    public function __construct(
        protected DynamoDbClient $dynamo,
        protected string $table = 'workbenchemps_recognition',
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

        dd($result);
        $item = $result['Item'] ?? null;
        if (!$item || empty($item['Name']['S'])) {
            return [null, null, null];
        }

        $nameRaw = $item['Name']['S']; // مثال: "Ali-123"
        $parts  = explode('-', $nameRaw);
        $empId  = trim(array_pop($parts));          // آخر جزء = ID
        $name   = trim(implode('-', $parts));

        $employee = $empId ? Employee::find($empId) : null;

        return [$name, $empId, $employee];
    }
}
