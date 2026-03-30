<?php

declare(strict_types=1);

namespace App\Services\AWS\Textract\Helpers;

class GenericReportSummaryParser
{
    public function parse(array $lines): array
    {
        $lines = $this->normalizeLines($lines);

        return [
            'branch_name' => $this->extractBranchName($lines),
            'date' => $this->extractDate($lines),
            'service_charge' => $this->extractServiceCharge($lines),
            'net_sale' => $this->extractNetSale($lines),
        ];
    }

    private function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            $line = preg_replace('/\s+/', ' ', $line);
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $normalized[] = $line;
        }

        return array_values($normalized);
    }

    private function extractBranchName(array $lines): ?string
    {
        $headerLines = array_slice($lines, 0, 12);

        foreach ($headerLines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if ($this->isMetaLine($line)) {
                continue;
            }

            if ($this->isDateLine($line)) {
                continue;
            }

            if ($this->isNumericOnly($line)) {
                continue;
            }

            if ($this->looksLikeAddressLine($line)) {
                continue;
            }

            if ($this->looksLikeSectionHeader($line)) {
                continue;
            }

            if (! $this->containsLetters($line)) {
                continue;
            }

            return $line;
        }

        return null;
    }

    private function extractDate(array $lines): ?string
    {
        $dates = [];

        foreach ($lines as $line) {
            if (preg_match_all('/\b(\d{2}[\/\-]\d{2}[\/\-]\d{4})\b/', $line, $matches)) {
                foreach ($matches[1] as $date) {
                    $date = str_replace('-', '/', $date);
                    $timestamp = \DateTime::createFromFormat('d/m/Y', $date)?->getTimestamp();

                    if ($timestamp !== false && $timestamp !== null) {
                        $dates[] = [
                            'value' => $date,
                            'timestamp' => $timestamp,
                        ];
                    }
                }
            }
        }

        if (empty($dates)) {
            return null;
        }

        usort($dates, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $dates[0]['value'];
    }

    private function extractServiceCharge(array $lines): ?float
    {
        $chargeSection = $this->extractSection($lines, ['chargesummary'], [
            'deliverysummary',
            'pickupsummary',
            'sourcesummary',
            'drawersummary',
            'salessummary',
        ]);

        $searchLines = ! empty($chargeSection) ? $chargeSection : $lines;

        return $this->extractAmountNearNormalizedLabels($searchLines, [
            'servicecharge',
        ]);
    }

    private function extractNetSale(array $lines): ?float
    {
        $salesSection = $this->extractSection($lines, ['salessummary'], [
            'drawersummary',
            'chargesummary',
            'deliverysummary',
            'pickupsummary',
            'sourcesummary',
        ]);

        $searchLines = ! empty($salesSection) ? $salesSection : $lines;

        return $this->extractAmountNearNormalizedLabels($searchLines, [
            'netsale',
            'nettsale',
            'netsales',
            'nettsales',
        ]);
    }

    private function extractSection(array $lines, array $startLabels, array $endLabels): array
    {
        $startIndex = null;
        $endIndex = null;

        foreach ($lines as $index => $line) {
            $normalized = $this->normalizeLabel($line);

            if ($startIndex === null && $this->matchesAnyLabel($normalized, $startLabels)) {
                $startIndex = $index;
                continue;
            }

            if ($startIndex !== null && $index > $startIndex && $this->matchesAnyLabel($normalized, $endLabels)) {
                $endIndex = $index;
                break;
            }
        }

        if ($startIndex === null) {
            return [];
        }

        if ($endIndex === null) {
            return array_slice($lines, $startIndex);
        }

        return array_slice($lines, $startIndex, $endIndex - $startIndex);
    }

    private function extractAmountNearNormalizedLabels(array $lines, array $targetLabels): ?float
    {
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $normalized = $this->normalizeLabel($lines[$i]);

            if (! $this->matchesAnyLabelLoosely($normalized, $targetLabels)) {
                continue;
            }

            $sameLineAmount = $this->extractAmountFromText($lines[$i]);
            if ($sameLineAmount !== null) {
                return $sameLineAmount;
            }

            for ($offset = 1; $offset <= 3; $offset++) {
                if (! isset($lines[$i + $offset])) {
                    continue;
                }

                $amount = $this->extractAmountFromText($lines[$i + $offset]);
                if ($amount !== null) {
                    return $amount;
                }
            }
        }

        return null;
    }

    private function extractAmountFromText(string $text): ?float
    {
        if (preg_match('/-?\d{1,3}(?:,\d{3})*(?:\.\d{2})|-?\d+\.\d{2}/', $text, $matches)) {
            return (float) str_replace(',', '', $matches[0]);
        }

        return null;
    }

    private function normalizeLabel(string $text): string
    {
        $text = strtoupper($text);
        $text = preg_replace('/[^A-Z0-9]+/', '', $text);

        return (string) $text;
    }

    private function matchesAnyLabel(string $normalized, array $labels): bool
    {
        foreach ($labels as $label) {
            if ($normalized === strtoupper($label)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAnyLabelLoosely(string $normalized, array $labels): bool
    {
        foreach ($labels as $label) {
            $label = strtoupper($label);

            if (str_contains($normalized, $label)) {
                return true;
            }
        }

        return false;
    }

    private function isMetaLine(string $line): bool
    {
        $normalized = $this->normalizeLabel($line);

        $metaPatterns = [
            'REGISTRATIONNO',
            'SSTID',
            'GENERATEDAT',
            'CLOSEUP',
            'TOTALBILLCOUNT',
            'TOTALSALES',
            'TOTALPAX',
            'TOTALDISCOUNT',
            'TOTALVOID',
            'TOTALREFUND',
        ];

        foreach ($metaPatterns as $pattern) {
            if (str_contains($normalized, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeAddressLine(string $line): bool
    {
        $normalized = strtoupper($line);

        $addressHints = [
            'JALAN',
            'ROAD',
            'STREET',
            'TAMAN',
            'SELANGOR',
            'MALAYSIA',
            'KUALA',
            'LUMPUR',
            'AMPANG',
        ];

        foreach ($addressHints as $hint) {
            if (str_contains($normalized, $hint)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeSectionHeader(string $line): bool
    {
        $normalized = $this->normalizeLabel($line);

        $headers = [
            'OVERALLSUMMARY',
            'CHARGESUMMARY',
            'DELIVERYSUMMARY',
            'PICKUPSUMMARY',
            'SOURCESUMMARY',
            'DRAWERSUMMARY',
            'SALESSUMMARY',
            'AMOUNT',
            'QTY',
        ];

        foreach ($headers as $header) {
            if ($normalized === $header) {
                return true;
            }
        }

        return false;
    }

    private function isDateLine(string $line): bool
    {
        return preg_match('/\b\d{2}[\/\-]\d{2}[\/\-]\d{4}\b/', $line) === 1;
    }

    private function isNumericOnly(string $line): bool
    {
        return preg_match('/^[\d\s.,\-:%]+$/', $line) === 1;
    }

    private function containsLetters(string $line): bool
    {
        return preg_match('/[A-Za-z]/', $line) === 1;
    }
}
