<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource;
use Aws\Rekognition\RekognitionClient;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HrEmployeeCameraPage extends Page
{
    protected static string $resource = HrEmployeeSearchResultResource::class;
    protected static ?string $navigationIcon = 'heroicon-o-camera';
    // protected static string $view = 'filament.pages.hr-employee-camera-page';
    protected static string $view = 'filament.clusters.h-r-attenance-cluster.resources.hr-employee-search-result-resource.pages.hr-employee-camera-page';

    public $capturedImage;

    protected $listeners = ['captureImage' => 'processImage'];

    public function processImage($imageData)
    {
        // Decode the base64 image data
        $this->capturedImage = $imageData;
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageData));

        // Initialize the Rekognition client
        $client = new RekognitionClient([
            'region' => 'your-region',
            'version' => 'latest',
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        // Call the recognition function
        $searchResult = $this->searchSimilarUserByImage($client, $imageData);

        // Flash result message
        if ($searchResult) {
            session()->flash('message', 'Face match found! Similarity: ' . $searchResult['similarity']);
        } else {
            session()->flash('message', 'No match found.');
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
