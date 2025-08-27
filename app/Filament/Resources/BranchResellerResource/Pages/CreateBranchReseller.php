<?php

namespace App\Filament\Resources\BranchResellerResource\Pages;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BranchResellerResource;
use App\Models\Branch;
use App\Models\Store;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateBranchReseller extends CreateRecord
{
    protected static string $resource = BranchResellerResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = Branch::TYPE_RESELLER;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $branch = Branch::create($data);

            // if ($branch->type === Branch::TYPE_RESELLER) {
                // $store = Store::create([
                //     'name'      => $branch->name . ' Store',
                //     'active'    => true,
                //     'branch_id' => $branch->id,
                // ]);

                // $branch->update(['store_id' => $store->id]);
            // }

            return $branch;
        });
    }
}