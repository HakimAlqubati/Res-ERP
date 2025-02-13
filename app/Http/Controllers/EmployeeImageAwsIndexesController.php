<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Illuminate\Support\Facades\Log;

class EmployeeImageAwsIndexesController extends Controller
{
    public function indexImages()
    {
        // AWS Rekognition and DynamoDB clients
        $rekognitionClient = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        $dynamoDbClient = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        // Define the images and employee details
        $images = [
            [
                'ImageName' => 'employees/puJRdBPtlXkoWjJ3.jpg',
                'ExternalImageId' => 'OOOOOOOOOOO',
                'EmployeeName' =>'144-OOOOOOOOOOOOOO OOOOOOOOOOOO',
            ],
        ];

        foreach ($images as $image) {
            try {
                // Index face in Rekognition
                $result = $rekognitionClient->indexFaces([
                    'CollectionId' => 'workbenchemps2', // Your collection ID
                    'Image' => [
                        'S3Object' => [
                            'Bucket' => env('AWS_BUCKET'),
                            'Name' => $image['ImageName'],
                        ],
                    ],
                    'ExternalImageId' => $image['ExternalImageId'], // Associate ID
                    'DetectionAttributes' => ['DEFAULT'],
                ]);

                // Log the result for verification
                Log::info('Indexed face', ['result' => $result]);

                // Extract the Rekognition FaceId
                $faceId = $result['FaceRecords'][0]['Face']['FaceId'];

                // Store metadata in DynamoDB
                $dynamoDbClient->putItem([
                    'TableName' => 'workbenchemps_recognition',
                    'Item' => [
                        'RekognitionId' => ['S' => $faceId],
                        'Name' => ['S' => $image['EmployeeName']],
                        'AvatarUrl' => ['S' => "s3://workbenchemps2/{$image['ImageName']}"],
                    ],
                ]);

                echo "Indexed and stored data for {$image['EmployeeName']} successfully.\n";
            } catch (Exception $e) {
                echo "Failed to index or store data for {$image['EmployeeName']}: {$e->getMessage()}\n";
            }
        }


        return 'Faces indexed successfully.';
    }
}
