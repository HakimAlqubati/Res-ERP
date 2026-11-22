<?php

declare(strict_types=1);

namespace App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource\Pages;

use App\Filament\Clusters\HRAttendanceReport\Resources\EmployeeDocumentReportResource;
use App\Modules\HR\EmployeeDocuments\Builders\EmployeeDocumentQueryBuilder;
use App\Modules\HR\EmployeeDocuments\Contracts\DocumentReportServiceInterface;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentReportFilterDTO;
use App\Modules\HR\EmployeeDocuments\DTOs\DocumentSummaryDTO;
use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Modules\HR\EmployeeDocuments\Exports\EmployeeDocumentsReportExport;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListEmployeeDocumentReport extends ListRecords
{
    protected static string $resource = EmployeeDocumentReportResource::class;

    private ?DocumentSummaryDTO $cachedSummary = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label(__('lang.export_excel'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();

                    if (! (clone $query)->exists()) {
                        Notification::make()
                            ->title(__('lang.no_data'))
                            ->body(__('lang.no_records_to_export'))
                            ->warning()
                            ->send();

                        return null;
                    }

                    $fileName = 'employee_documents_report_' . now()->format('Y-m-d_His') . '.xlsx';

                    return Excel::download(
                        new EmployeeDocumentsReportExport(prebuiltQuery: $query),
                        $fileName
                    );
                }),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        $requested = request()->query('activeTab');

        if (is_string($requested) && array_key_exists($requested, $this->getCachedTabs())) {
            return $requested;
        }

        return parent::getDefaultActiveTab();
    }

    public function getTabs(): array
    {
        $summary = $this->resolveSummary();

        return [
            'all' => Tab::make(__('lang.all'))
                ->icon('heroicon-o-document-text')
                ->badge($summary->total)
                ->badgeColor('primary'),

            DocumentExpiryStatus::Expired->value => Tab::make(DocumentExpiryStatus::Expired->label())
                ->icon(DocumentExpiryStatus::Expired->icon())
                ->badge($summary->expired)
                ->badgeColor(DocumentExpiryStatus::Expired->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => EmployeeDocumentQueryBuilder::applyStatusCondition($query, DocumentExpiryStatus::Expired)),

            DocumentExpiryStatus::ExpiringSoon->value => Tab::make(DocumentExpiryStatus::ExpiringSoon->label())
                ->icon(DocumentExpiryStatus::ExpiringSoon->icon())
                ->badge($summary->expiringSoon)
                ->badgeColor(DocumentExpiryStatus::ExpiringSoon->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => EmployeeDocumentQueryBuilder::applyStatusCondition($query, DocumentExpiryStatus::ExpiringSoon)),

            DocumentExpiryStatus::Valid->value => Tab::make(DocumentExpiryStatus::Valid->label())
                ->icon(DocumentExpiryStatus::Valid->icon())
                ->badge($summary->valid)
                ->badgeColor(DocumentExpiryStatus::Valid->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => EmployeeDocumentQueryBuilder::applyStatusCondition($query, DocumentExpiryStatus::Valid)),

            DocumentExpiryStatus::NoExpiry->value => Tab::make(DocumentExpiryStatus::NoExpiry->label())
                ->icon(DocumentExpiryStatus::NoExpiry->icon())
                ->badge($summary->noExpiry)
                ->badgeColor(DocumentExpiryStatus::NoExpiry->color())
                ->modifyQueryUsing(fn (Builder $query): Builder => EmployeeDocumentQueryBuilder::applyStatusCondition($query, DocumentExpiryStatus::NoExpiry)),
        ];
    }

    private function resolveSummary(): DocumentSummaryDTO
    {
        if ($this->cachedSummary !== null) {
            return $this->cachedSummary;
        }

        return $this->cachedSummary = app(DocumentReportServiceInterface::class)->getSummary(
            new DocumentReportFilterDTO(
                onlyActiveEmployees: true,
                onlyActiveFiles: true,
                applyAccessScope: true,
            )
        );
    }
}
