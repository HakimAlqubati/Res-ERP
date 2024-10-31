<?php

namespace App\Models;

use Aws\DynamoDb\DynamoDbClient;
use Illuminate\Database\Eloquent\Model;

class EmployeeAWS
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

    public function addEmployee($rekognitionId, $name, $avatarUrl)
    {
        $this->dynamoDb->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'RekognitionId' => ['S' => $rekognitionId],
                'Name' => ['S' => $name],
                'AvatarUrl' => ['S' => $avatarUrl],
            ],
        ]);
    }
}
