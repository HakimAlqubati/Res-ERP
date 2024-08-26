<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function afterCreate(): void
    {
        $user_id = $this->record->id;
        User::find($user_id)->update(['role_id' => 10]);
        DB::table('model_has_roles')->insert([
            'role_id' => 10,
            'model_id' => $user_id,
            'model_type' => 'App\Models\User'
        ]);
    }

    // protected function mutateFormDataBeforeSave(array $data): array
    // {
    //     dd($data);
    //     return $data;
    // }

}
