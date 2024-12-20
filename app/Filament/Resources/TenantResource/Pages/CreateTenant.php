<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;
    protected ?bool $hasDatabaseTransactions = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function create(bool $another = false): void
    {
        DB::beginTransaction();
        try {
            parent::create($another);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error in CreateTenant:', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            showWarningNotifiMessage($th->getMessage());
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['domain'] = $data['domain'] . '.' . config('app.domain');
        return $data;
    }
    protected function afterCreate(): void
    {
        DB::beginTransaction();
        try {
            TenantResource::createDatabase($this->record->database);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error in afterCreate:', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
            showWarningNotifiMessage($th->getMessage());
        }
    }
}
