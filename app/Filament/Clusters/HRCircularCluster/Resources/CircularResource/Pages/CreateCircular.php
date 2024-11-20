<?php

namespace App\Filament\Clusters\HRCircularCluster\Resources\CircularResource\Pages;

use App\Filament\Clusters\HRCircularCluster\Resources\CircularResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCircular extends CreateRecord
{
    protected static string $resource = CircularResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        $data['branch_ids'] = isset($data['branch_ids'])?json_encode($data['branch_ids']) : '[]';
        return $data;
    }

    public function afterCreate(): void
    {
        if (is_array($this->data['file_path']) && count($this->data['file_path']) > 0) {
            foreach ($this->data['file_path'] as $key => $image) {
                $this->record->photos()->create([
                    'image_name' => $image,
                    'image_path' => $image,
                    'created_by' => auth()->user()->id,
                ]);
            }
        }

    }
}
