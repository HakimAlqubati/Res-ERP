<?php

namespace App\Http\Controllers;

use Aws\Rekognition\RekognitionClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceRecognitionController extends Controller
{
    public function recognize(Request $request)
    {
        $imageData = $request->input('image'); // Get the image data
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));

        // Check image size
        if (strlen($imageData) > 5 * 1024 * 1024) { // 5MB limit
            return response()->json(['message' => 'Image size exceeds 5MB limit.'], 400);
        }
        // Initialize the Rekognition client
        $client = awsClient();
        Log::info('First few bytes of image data: ' . substr($imageData, 0, 20));

        // Call the recognition function
        $searchResult = $this->searchSimilarUserByImage($client, $imageData);

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

    private function searchSimilarUserByImage(RekognitionClient $client, $uploadedImageData)
    {
        $similarityThreshold = 80;
        $employees = \App\Models\Employee::whereNotNull('avatar')->get();

        foreach ($employees as $employee) {
            $avatarPath = Storage::path('public/' . $employee->avatar);
            $avatarImage = file_get_contents($avatarPath);

            try {
                $result = $client->compareFaces([
                    'SourceImage' => ['Bytes' => $uploadedImageData],
                    'TargetImage' => ['Bytes' => $avatarImage],
                    'SimilarityThreshold' => $similarityThreshold,
                ]);

                if (isset($result['FaceMatches']) && count($result['FaceMatches']) > 0) {
                    return [
                        'employee_id' => $employee->id,
                        'similarity' => $result['FaceMatches'][0]['Similarity'],
                    ];
                }
            } catch (\Aws\Exception\AwsException $e) {
                Log::error('Rekognition error: ' . $e->getMessage());
            }
        }

        return null;
    }
}
