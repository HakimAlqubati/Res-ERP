<?php

namespace App\DTOs\Financial;

class FinancialCategoryReportDTO
{
    public function __construct(
        public readonly string $generatedAt,
        public readonly array $filtersApplied,
        public readonly ?string $dateRangeStart,
        public readonly ?string $dateRangeEnd,
        public readonly FinancialCategoryStatisticsDTO $statistics,
        public readonly array $categorySummaries,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'report_info' => [
                'generated_at' => $this->generatedAt,
                'date_range' => [
                    'start' => $this->dateRangeStart,
                    'end' => $this->dateRangeEnd,
                ],
                'filters_applied' => $this->filtersApplied,
            ],
            'statistics' => $this->statistics->toArray(),
            'category_summaries' => array_map(
                fn($summary) => $summary instanceof CategoryTransactionSummaryDTO 
                    ? $summary->toArray() 
                    : $summary,
                $this->categorySummaries
            ),
            'metadata' => $this->metadata,
        ];
    }
}
