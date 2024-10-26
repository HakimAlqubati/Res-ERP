<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource\Pages;

use App\Filament\Clusters\HRAttenanceCluster\Resources\HrEmployeeSearchResultResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHrEmployeeSearchResult extends CreateRecord
{
    protected static string $resource = HrEmployeeSearchResultResource::class;
    protected function afterCreate(): void
    {
        // Get Rekognition client instance
        $client = awsClient();

        // Run the comparison for the newly created record
        $record = $this->record;

        $image = 'storage/' . $record->image;
       

        $searchResult = searchSimilarUserByImage($client, $image);

        // dd($searchResult);
        // Update record with search results
        if ($searchResult) {
            $record->employee_id = $searchResult['employee_id'];
            $record->similarity = $searchResult['similarity'];
        } else {
            $record->similarity = 0; // No match found
        }

        $record->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
