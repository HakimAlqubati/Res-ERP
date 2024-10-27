<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceRecognitionController extends Controller
{
    public function recognize(Request $request)
    {
        $imageData = $request->input('image'); // استلام بيانات الصورة

        // فك تشفير بيانات الصورة
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));

        // التحقق من حجم الصورة
        if (strlen($imageData) > 5 * 1024 * 1024) { // الحد الأقصى 5 ميغابايت
            return response()->json(['message' => 'Image size exceeds 5MB limit.'], 400);
        }

        // رفع الصورة إلى التخزين
        $filePath = 'public/to_compare/' . time() . '.png'; // يمكنك تغيير المسار حسب رغبتك
        Storage::put($filePath, $imageData); // رفع الصورة إلى المسار المحدد
        // Initialize the Rekognition client using the helper function
        $client = awsClient();
        // Log::warning("send_uploaded ". $imageData);
        // Call the recognition function
        $searchResult = $this->searchSimilarUserByImage($client, $imageData, $filePath);

        // Return the result as JSON
        if ($searchResult) {
            return response()->json([
                'message' => 'Face match found!',
                'employee_id' => $searchResult['employee_id'],
                'similarity' => $searchResult['similarity'],
            ]);
        } else {
            return response()->json(['message' => 'No match found.'], 404);
        }
    }

    private function searchSimilarUserByImage(RekognitionClient $client, string $uploadedImageData, string $uploadedImagePath): ?array
    {
        $similarityThreshold = 80;

        // Fetch all employees who have an avatar
        $employees = \App\Models\Employee::whereNotNull('avatar')->orderBy('id','desc')->get();

        // Log the size of the uploaded image data
        Log::info('Uploaded image size: ' . strlen($uploadedImageData) . ' bytes');
        Log::info('Uploaded image path: ' . $uploadedImagePath);

        foreach ($employees as $employee) {
            // Get the path to the employee's avatar
            $avatarPath = Storage::path('public/' . $employee->avatar);

            // Check if the avatar file exists before trying to read it
            if (!file_exists($avatarPath)) {
                Log::warning('Avatar file does not exist for employee ID: ' . $employee->id);
                continue; // Skip to the next employee if the avatar does not exist
            }

            // Read the avatar image file contents
            $avatarImage = file_get_contents($avatarPath);
            // $imageUploaded = file_get_contents('storage/employees/employee-girl-green-hejab.jpeg');

            // Log sizes of the images being compared
            Log::info('Comparing images for employee ID: ' . $employee->id);
            Log::info('Avatar image size: ' . strlen($avatarImage) . ' bytes');

            try {
                // Call the Rekognition compareFaces API
                $result = $client->compareFaces([
                    // 'SourceImage' => ['Bytes' => $imageUploaded], // Use the uploaded image bytes
                    'SourceImage' => ['Bytes' => $uploadedImageData], // Use the uploaded image bytes
                    'TargetImage' => ['Bytes' => $avatarImage], // Use the employee's avatar image bytes
                    'SimilarityThreshold' => $similarityThreshold,
                ]);

                // Check for face matches
                if (isset($result['FaceMatches']) && count($result['FaceMatches']) > 0) {
                    Log::info('Face match found for employee Name: ' . Employee::find($employee->id)?->name );
                    return [
                        'employee_id' => $employee->id,
                        'similarity' => $result['FaceMatches'][0]['Similarity'], // Return the first match's similarity score
                    ];
                } else {
                    Log::info('No face match found for employee ID: ' . $employee->id);
                }
            } catch (\Aws\Exception\AwsException $e) {
                // Log the specific error encountered with AWS Rekognition
                Log::error('Rekognition error for employee ID: ' . $employee->id . ' - ' . $e->getMessage());
            }
        }

        return null; // Return null if no matches are found
    }

}
