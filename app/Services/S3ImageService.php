<?php

namespace App\Services;

use App\Models\Employee;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class S3ImageService
{
    /**
     * Fetch all image URLs from the S3 bucket, optionally filtered by date.
     *
     * @param string|null $startDate Format: YYYY-MM-DD
     * @param string|null $endDate   Format: YYYY-MM-DD
     * @return array
     */
    public function getAllImages(?string $startDate = null, ?string $endDate = null): array
    {
        // Get all files in the bucket
        $files = Storage::disk('s3')->allFiles();

        // Filter files to include only images
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $images = array_filter($files, function ($file) use ($imageExtensions) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array(strtolower($extension), $imageExtensions);
        });

        // Filter images by date, if specified
        if ($startDate || $endDate) {
            $images = array_filter($images, function ($image) use ($startDate, $endDate) {
                $lastModified = Storage::disk('s3')->lastModified($image);
                $fileDate = Carbon::createFromTimestamp($lastModified);

                if ($startDate && $fileDate->lt(Carbon::parse($startDate))) {
                    return false;
                }

                if ($endDate && $fileDate->gt(Carbon::parse($endDate))) {
                    return false;
                }

                return true;
            });
        }

        // Generate URLs for the images
        return array_map(function ($image) {
            return Storage::disk('s3')->url($image);
        }, $images);
    }

    public static function indexEmployeeImage($employeeId)
    {
        // Retrieve the employee's details from the database
        $employee = Employee::find($employeeId);

        if (!$employee || !$employee->avatar) {
            return response()->json(['success' => false, 'message' => 'Employee not found or no avatar available'], 404);
        }

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

        // Define employee image details
        $imageName = $employee->avatar; // Assuming the avatar path is stored in the `avatar` field
        $externalImageId = "EMP-" . $employee->id;
        $employeeName = "{$employee->employee_no} - {$employee->name}";

        try {
            // Index face in Rekognition
            $result = $rekognitionClient->indexFaces([
                'CollectionId' => 'workbenchemps2', // Your Rekognition Collection ID
                'Image' => [
                    'S3Object' => [
                        'Bucket' => env('AWS_BUCKET'),
                        'Name' => $imageName, // Path of the employee's avatar in S3
                    ],
                ],
                'ExternalImageId' => $externalImageId, // Associate Rekognition with Employee ID
                'DetectionAttributes' => ['DEFAULT'],
            ]);

            // Log the result for verification
            Log::info('Indexed face for employee', ['result' => $result, 'employee_id' => $employee->id]);

            // Extract the Rekognition FaceId
            $faceId = $result['FaceRecords'][0]['Face']['FaceId'] ?? null;

            if (!$faceId) {
                return response()->json(['success' => false, 'message' => 'No face detected for this image'], 400);
            }

            // Store metadata in DynamoDB
            $dynamoDbClient->putItem([
                'TableName' => 'workbenchemps_recognition',
                'Item' => [
                    'RekognitionId' => ['S' => $faceId],
                    'Name' => ['S' => $employeeName],
                    'AvatarUrl' => ['S' => "s3://workbenchemps2/{$imageName}"],
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => "Indexed and stored data for Employee ID: {$employee->id} successfully.",
                'face_id' => $faceId,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => "Failed to index employee image: {$e->getMessage()}"], 500);
        }
    }
}
