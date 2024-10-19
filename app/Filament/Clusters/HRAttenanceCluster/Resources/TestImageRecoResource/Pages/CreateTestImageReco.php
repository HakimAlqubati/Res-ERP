<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\TestImageRecoResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\App;

class CreateTestImageReco extends CreateRecord
{
    protected static string $resource = TestImageRecoResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;

    }

    protected function afterCreate(): void
    {
        // Get the Rekognition client
        $client = awsClient(); // Ensure you have a function to retrieve this

        // Get the uploaded images
        $record = $this->record;

        $sourceImagePath = 'storage/' . $record->image;
        $targetImagePath = 'storage/' . $record->image2;

        // Compare images
        $comparisonResult = compareImages($client, $sourceImagePath, $targetImagePath);

        // Save the comparison result to the database
        $record->details = $comparisonResult['details'];
        $record->title = $comparisonResult['title']; // Optional, if you want to save the title

        $record->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
