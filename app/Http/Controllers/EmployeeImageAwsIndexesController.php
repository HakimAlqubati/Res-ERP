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
                    'ImageName' => 'employee-employee-korian-girl.jpeg',
                    'ExternalImageId' => 'employee-korian-girl',
                    'EmployeeName' => 'Jane Smith',
                ],
                [
                    'ImageName' => 'employee-girl-green-hejab.jpeg',
                    'ExternalImageId' => 'employee-green-hejab',
                    'EmployeeName' => 'Sapna-96',
                ],
                [
                    'ImageName' => '9654879698723-10-06accf102caaa970ce65d217b9ae9a8e9a57caa67c.jpg',
                    'ExternalImageId' => '9654879698723-10-06accf102caaa970ce65d217b9ae9a8e9a57caa67c',
                    'EmployeeName' => 'Kadem-125',
                ],
                [
                    'ImageName' => 'Almawqea2017-12-10-08-33-02-243608.jpg',
                    'ExternalImageId' => 'Almawqea2017-12-10-08-33-02-243608',
                    'EmployeeName' => 'Abo Bakr Salem-119',
                ],
            ];
    
            foreach ($images as $image) {
                try {
                    // Index face in Rekognition
                    $result = $rekognitionClient->indexFaces([
                        'CollectionId' => 'workbenchemps', // Your collection ID
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
                            'AvatarUrl' => ['S' => "s3://workbenchemps/{$image['ImageName']}"],
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
