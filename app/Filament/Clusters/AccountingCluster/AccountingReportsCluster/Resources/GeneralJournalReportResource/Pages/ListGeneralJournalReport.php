<?php

namespace App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\GeneralJournalReportResource\Pages;

use App\Filament\Clusters\AccountingCluster\AccountingReportsCluster\Resources\GeneralJournalReportResource;
use App\Models\JournalEntry;
use Filament\Resources\Pages\ListRecords;

class ListGeneralJournalReport extends ListRecords
{
    protected static string $resource = GeneralJournalReportResource::class;

    protected static string $view = 'filament.pages.accounting-reports.general-journal-report';

    protected function getViewData(): array
    {
        $filters = $this->getTable()->getFilters()['date_range']->getState() ?? [];

        $startDate = $filters['start_date'] ?? now()->startOfYear();
        $endDate = $filters['end_date'] ?? now();

        $entries = JournalEntry::with(['lines.account'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        return [
            'entries' => $entries,
            'dateRange' => ['from' => $startDate, 'to' => $endDate],
        ];
    }
}
