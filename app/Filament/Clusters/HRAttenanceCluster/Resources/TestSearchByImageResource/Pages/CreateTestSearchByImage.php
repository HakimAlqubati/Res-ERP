<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\TestSearchByImageResource;
use App\Models\TestSearchByImage;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateTestSearchByImage extends CreateRecord
{
    protected static string $resource = TestSearchByImageResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle the uploaded image
        $imagePath = $data['image']; // S3 path of the uploaded image in the format 'uploads/image.png'

        // Initialize the AWS Rekognition Client
        $rekognitionClient = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);


        // Initialize the AWS DynamoDB Client
        $dynamoDbClient = new DynamoDbClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);
        
        try {
              // Perform the Rekognition search
              $result = $rekognitionClient->searchFacesByImage([
                'CollectionId' => 'workbenchemps', // Replace with your collection ID
                'Image' => [
                    'S3Object' => [
                        'Bucket' => env('AWS_BUCKET'),
                        'Name' => $imagePath, // Use the S3 path of the uploaded image
                    ],
                ],
                'FaceMatchThreshold' => 90, // Confidence threshold
                'MaxFaces' => 1, // We only need the top match
            ]);

              // Log the Rekognition result for debugging
            Log::info('Rekognition SearchFacesByImage result:', ['result' => $result]);

    
            $rekognitionId = null;
            $name = null;
            
            if (!empty($result['FaceMatches'])) {
                // Get the Rekognition ID of the first matched face
                $rekognitionId = $result['FaceMatches'][0]['Face']['FaceId'];
            
                // Query DynamoDB for the corresponding name using the Rekognition ID
                $dynamoResult = $dynamoDbClient->getItem([
                    'TableName' => 'workbenchemps_recognition', // DynamoDB table name
                    'Key' => [
                        'RekognitionId' => [
                            'S' => $rekognitionId, // Rekognition ID as the partition key in DynamoDB
                        ],
                    ],
                ]);
            
                // Check if the item exists and retrieve the name
                if (!empty($dynamoResult['Item']) && isset($dynamoResult['Item']['Name'])) {
                    $name = $dynamoResult['Item']['Name']['S']; // Retrieve the 'Name' field
                } else {
                    $name = 'Name not found in DynamoDB';
                }
            } else {
                $name = 'No face match found';
            }
            
            // Update data with retrieved information
            $data['image_url'] = Storage::disk('s3')->url($imagePath); // Get the public URL of the image
            $data['rekognition_id'] = $rekognitionId ?? 'No Rekognition ID';
            $data['name'] = $name;
            
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'There are no faces in the image') !== false) {
                // Handle the "no faces" error gracefully
                $data['name'] = 'No face detected in the image';
                $data['rekognition_id'] = 0;
                $data['image_url'] = 'no url found';
            } else {
                // For other exceptions, throw the error
                throw new Exception('Error during AWS Rekognition search: ' . $e->getMessage());
            }
        }

        return $data;
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
