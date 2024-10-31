<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAWS;
use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeAWSController extends Controller
{
    protected $dynamoDb;
    protected $tableName = 'workbenchemps_recognition';

    public function __construct()
    {
        $this->dynamoDb = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
    }
    public function addEmployee(): JsonResponse
    {
        // Static employee data
        $employees = [
            [
                'rekognition_id' => 'emp001',
                'name' => 'John Doe',
                'avatar_url' => 's3://workbenchemps/employee-korian-girl.jpeg'
            ],
            [
                'rekognition_id' => 'emp002',
                'name' => 'Jane Smith',
                'avatar_url' => 's3://workbenchemps/employee-girl-green-hejab.jpeg'
            ]
        ];

        foreach ($employees as $employee) {
            $this->dynamoDb->putItem([
                'TableName' => $this->tableName,
                'Item' => [
                    'RekognitionId' => ['S' => $employee['rekognition_id']],
                    'Name' => ['S' => $employee['name']],
                    'AvatarUrl' => ['S' => $employee['avatar_url']],
                ],
            ]);
        }

        return response()->json(['message' => 'Employees added successfully!']);
    }
}
