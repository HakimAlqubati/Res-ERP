<?php

declare(strict_types=1);

namespace Tests\Unit\HR\EmployeeDocuments;

use App\Modules\HR\EmployeeDocuments\Support\DynamicFieldDateExtractor;
use PHPUnit\Framework\TestCase;

final class DynamicFieldDateExtractorTest extends TestCase
{
    private DynamicFieldDateExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new DynamicFieldDateExtractor();
    }

    public function test_extracts_standard_issue_and_expiry_dates_from_array(): void
    {
        $result = $this->extractor->extract([
            'passport_number' => 'A12345678',
            'issue_date' => '2024-01-10',
            'expiry_date' => '2029-01-09',
        ]);

        $this->assertSame('2024-01-10', $result['issue_date']);
        $this->assertSame('2029-01-09', $result['expiry_date']);
        $this->assertSame([], $result['unmapped_date_keys']);
    }

    public function test_extracts_dates_from_double_encoded_json(): void
    {
        $doubleEncoded = json_encode(json_encode([
            'start_date' => '2025-03-01',
            'end_date' => '2027-03-01',
        ]));

        $result = $this->extractor->extract($doubleEncoded);

        $this->assertSame('2025-03-01', $result['issue_date']);
        $this->assertSame('2027-03-01', $result['expiry_date']);
    }

    public function test_ignores_invalid_or_numeric_values_and_reports_unmapped_date_fields(): void
    {
        $result = $this->extractor->extract(
            [
                'expiry_date' => 'not-a-date',
                'passport_number' => '123456',
                'medical_check_date' => '2026-08-20',
            ],
            [
                ['field_name' => 'medical_check_date', 'field_type' => 'date'],
                ['field_name' => 'passport_number', 'field_type' => 'number'],
            ]
        );

        $this->assertNull($result['issue_date']);
        $this->assertNull($result['expiry_date']);
        $this->assertSame(['medical_check_date'], $result['unmapped_date_keys']);
    }

    public function test_handles_alternative_key_names_with_spaces_and_dashes(): void
    {
        $result = $this->extractor->extract([
            'Date-of-Issue' => '2023-05-01',
            'Valid Until' => '2028-05-01',
        ]);

        $this->assertSame('2023-05-01', $result['issue_date']);
        $this->assertSame('2028-05-01', $result['expiry_date']);
    }
}
