<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\CustomTenantModel;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
    protected ?bool $hasDatabaseTransactions = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // public function create(bool $another = false): void
    // {
    //     try {
    //         DB::transaction(function () use ($another) {
    //             parent::create($another);
    //         });
    //     } catch (\Throwable $th) {
    //         Log::error('Error in create method:', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
    //         showWarningNotifiMessage($th->getMessage());
    //     }
    // }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['domain'] = $data['domain'] . '.' . config('app.domain');
        return $data;
    }

    protected function afterCreate(): void
    {
        (new CustomTenantModel())->importDatabase($this->record);
    }
    // protected function afterCreate(): void
    // {
    // try {
    //     TenantResource::createDatabase($this->record->database);
    // } catch (\Throwable $th) {
    //     Log::error('Error in afterCreate:', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
    //     throw $th; // Let the main transaction handle rollback
    // }
    // }
}
