<?php
namespace App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource\Pages;

use App\Filament\Clusters\HRSalaryCluster\Resources\PenaltyDeductionResource;
use App\Services\HR\MonthClosure\MonthClosureService;
use Exception;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentView;
use Throwable;

class CreatePenaltyDeduction extends CreateRecord
{
    protected static string $resource        = PenaltyDeductionResource::class;
    protected ?bool $hasDatabaseTransactions = true;

    public function create(bool $another = false): void
    {
        $data = $this->form->getState();
        try {
            app(MonthClosureService::class)->ensureMonthIsOpenByYearMonth($data['year'], $data['month']);
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->callHook('afterCreate');
        } catch (Halt $exception) {
            showWarningNotifiMessage($exception->getMessage());
            $exception->shouldRollbackDatabaseTransaction() ?
            $this->rollBackDatabaseTransaction() :
            $this->commitDatabaseTransaction();

            return;
        } catch (Exception $exception) {
            showWarningNotifiMessage($exception->getMessage());
            $this->rollBackDatabaseTransaction();

            // throw $exception;
        }

        $this->commitDatabaseTransaction();

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->redirect(static::getResource()::getUrl('index'));
            $this->record = null;

            $this->fillForm();

            return;
        }

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect(static::getResource()::getUrl('index'));
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->user()->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}