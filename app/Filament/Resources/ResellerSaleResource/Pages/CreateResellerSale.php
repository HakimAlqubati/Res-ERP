<?php

namespace App\Filament\Resources\ResellerSaleResource\Pages;

use App\Filament\Resources\ResellerSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateResellerSale extends CreateRecord
{
    protected static string $resource = ResellerSaleResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function create(bool $another = false): void
    {
        try {
            parent::create($another); 
        } catch (\Exception $e) {

            showWarningNotifiMessage('Error',       $e->getMessage());

            // throw $e; // للسماح لـ Filament بإظهار الخطأ أيضًا إن لزم
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['store_id'] = $this->record->branch->store_id ?? \App\Models\Branch::find($data['branch_id'])?->store_id;
        if ($data['store_id'] == null) {
        }
        return $data;
    }
    
}