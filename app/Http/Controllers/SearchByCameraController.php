<?php

namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SearchByCameraController extends Controller
{
    public function process(Request $request)
    {
        // Decode the base64 image
        $imageData = $request->input('capturedImage');
        $imageData = str_replace(['data:image/jpeg;base64,', 'data:image/png;base64,'], '', $imageData);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            throw new Exception('Base64 decoding failed');
        }

        // Upload image to S3
        $fileName = 'captured_' . time() . '.jpg';
        // Storage::disk('s3')->put("uploads/{$fileName}", $imageData, [
        //     'visibility' => 'public',
        //     'ContentType' => 'image/jpeg'
        // ]);

        Storage::disk('s3')->put("uploads/{$fileName}", $imageData, [
            'visibility' => 'private',
            'ContentType' => 'image/jpeg'
        ]);
        
        // Initialize Rekognition client
        $rekognitionClient = new RekognitionClient([
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        // Search for face in Rekognition
        try {
            $result = $rekognitionClient->searchFacesByImage([
                'CollectionId' => 'workbenchemps2',
                'Image' => [
                    'S3Object' => [
                        'Bucket' => env('AWS_BUCKET'),
                        'Name' => "uploads/{$fileName}",
                    ],
                ],
                'FaceMatchThreshold' => 90,
                'MaxFaces' => 1,
            ]);

            $rekognitionId = null;
            $name = 'No match found';

            // Check if there's a match
            if (!empty($result['FaceMatches'])) {
                $rekognitionId = $result['FaceMatches'][0]['Face']['FaceId'];

                // Retrieve employee name from DynamoDB
                $dynamoDbClient = new DynamoDbClient([
                    'region' => env('AWS_DEFAULT_REGION'),
                    'version' => 'latest',
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                $dynamoResult = $dynamoDbClient->getItem([
                    'TableName' => 'workbenchemps_recognition',
                    'Key' => [
                        'RekognitionId' => [
                            'S' => $rekognitionId,
                        ],
                    ],
                ]);

                if (!empty($dynamoResult['Item']['Name']['S'])) {
                    $name = $dynamoResult['Item']['Name']['S'];
                }
            }

            // Return a view with search results
            return redirect()->back()->with('status', "Match Found: {$name}");

        } catch (Exception $e) {
            Log::error("Rekognition search error: " . $e->getMessage());
            return redirect()->back()->withErrors("Error: " . $e->getMessage());
        }
    }
}
