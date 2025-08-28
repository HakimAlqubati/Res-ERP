<?php

namespace App\Http\Controllers;

use App\Filament\Pages\AttendanecEmployee2;
use App\Models\Attendance;
use App\Models\Employee;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use Aws\Rekognition\RekognitionClient;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                'rekognition_id' => '146',
                'name' => '146-yahia',
                'avatar_url' => 's3://workbenchemps2/yahia.jpeg',
            ],
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

    public function uploadCapturedImage(Request $request)
    {
        // Validate the request to ensure 'image' data is present
        $request->validate([
            'image' => 'required|string',
        ]);

        // Decode the Base64 image
        $imageData = $request->input('image');
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageData = base64_decode($imageData);

        // Generate a unique filename
        $fileName = 'captured_face_' . time() . '.png';

        // Save the image to S3
        Storage::disk('s3')->put("uploads/{$fileName}", $imageData, [
            'visibility' => 'private',
            'ContentType' => 'image/jpeg',
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

        return $this->handleFaceRecognition($fileName, $rekognitionClient);
    }

    private function handleFaceRecognition($fileName, $rekognitionClient)
    {
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

            return response()->json(['status' => 'success', 'message' => $name]);
        } catch (Exception $e) {
            Log::error("Face recognition error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => "Error: " . $e->getMessage()]);
        }
    }

    public function uploadCapturedImage_old(Request $request)
    {

        // Validate the request to ensure 'image' data is present
        $request->validate([
            'image' => 'required|string',
        ]);

        // Decode the Base64 image
        $imageData = $request->input('image');
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageData = base64_decode($imageData);

        // Generate a unique filename
        $fileName = 'captured_face_' . time() . '.png';

        // Save the image to S3 and generate URL
        $path = "uploads/{$fileName}";
        // Save the image to the S3 bucket
        Storage::disk('s3')->put($path, $imageData, [
            'visibility' => 'private',
            'ContentType' => 'image/jpeg',
        ]);

        // Log::info('image_uploaded', [$path]);

        // Generate the S3 URL for the uploaded image
        // Generate a temporary signed URL
        // $imgUrl = Storage::disk('s3')->temporaryUrl(
        //     $path,
        //     now()->addMinutes(60)  // Expire after 60 minutes
        // );
        // Store image details in attendance_images_uploaded table

        $uploadedImage = \App\Models\AttendanceImagesUploaded::create([
            'img_url' => $path,
            'datetime' => now(),
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

            Log::info('rekognition_result', [$result]);

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
            Log::info('namefromsearch', [$name]);
            $expodedResult = explode('-', $name);

            $employeeId = $expodedResult[1] ?? 0;
            $employeeName = $expodedResult[0] ?? 'Employee not found';
            $name = $employeeName;


            $employee = Employee::find($employeeId);
            if ($uploadedImage && $employee) {
                $uploadedImage->employee_id = $employeeId;
                $uploadedImage->save();
            }
            if ($employee) {
                // $date = now()->toDateString();
                // $time = now()->toTimeString();
                // $time = $_GET['time'];
                // $date = $_GET['date'];
                $time = now()->toTimeString();
                $date = now()->toDateString();
                (new AttendanecEmployee2(Attendance::ATTENDANCE_TYPE_WEBCAM))->handleCreationAttendance($employeeId, $date, $time);

                Log::info('employee_data_captured', [$employee]);
            } else {

                Log::info('employee_data_captured', ['There is no employee']);
            }
            // Return JSON response with the match result
            return response()->json(['status' => 'success', 'message' => "{$name}"]);
        } catch (Exception $e) {
            Log::error("Rekognition search error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => "Error: " . $e->getMessage()]);
        }
    }
}
