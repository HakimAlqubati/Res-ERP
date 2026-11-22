<?php

declare(strict_types=1);

namespace Tests\Unit\HR\EmployeeDocuments;

use App\Modules\HR\EmployeeDocuments\Enums\DocumentExpiryStatus;
use App\Modules\HR\EmployeeDocuments\Support\DocumentStatusCalculator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class DocumentStatusCalculatorTest extends TestCase
{
    private DocumentStatusCalculator $calculator;

    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new DocumentStatusCalculator();
        $this->today = CarbonImmutable::parse('2026-06-15');
    }

    public function test_returns_no_expiry_when_file_type_has_no_expiry(): void
    {
        $status = $this->calculator->resolveStatus(false, '2026-06-01', 30, $this->today);
        $remaining = $this->calculator->remainingDays(false, '2026-06-01', $this->today);

        $this->assertSame(DocumentExpiryStatus::NoExpiry, $status);
        $this->assertNull($remaining);
    }

    public function test_returns_no_expiry_when_expiry_date_is_null(): void
    {
        $status = $this->calculator->resolveStatus(true, null, 30, $this->today);
        $remaining = $this->calculator->remainingDays(true, null, $this->today);

        $this->assertSame(DocumentExpiryStatus::NoExpiry, $status);
        $this->assertNull($remaining);
    }

    public function test_returns_expired_for_yesterday(): void
    {
        $status = $this->calculator->resolveStatus(true, '2026-06-14', 30, $this->today);
        $remaining = $this->calculator->remainingDays(true, '2026-06-14', $this->today);

        $this->assertSame(DocumentExpiryStatus::Expired, $status);
        $this->assertSame(-1, $remaining);
    }

    public function test_returns_expiring_soon_for_today_and_boundary_day(): void
    {
        // Today (0 days left)
        $this->assertSame(
            DocumentExpiryStatus::ExpiringSoon,
            $this->calculator->resolveStatus(true, '2026-06-15', 30, $this->today)
        );
        $this->assertSame(0, $this->calculator->remainingDays(true, '2026-06-15', $this->today));

        // Exact boundary (90 days custom alert window)
        $this->assertSame(
            DocumentExpiryStatus::ExpiringSoon,
            $this->calculator->resolveStatus(true, '2026-09-13', 90, $this->today)
        );
        $this->assertSame(90, $this->calculator->remainingDays(true, '2026-09-13', $this->today));
    }

    public function test_returns_valid_one_day_past_custom_alert_window(): void
    {
        $status = $this->calculator->resolveStatus(true, '2026-09-14', 90, $this->today);
        $remaining = $this->calculator->remainingDays(true, '2026-09-14', $this->today);

        $this->assertSame(DocumentExpiryStatus::Valid, $status);
        $this->assertSame(91, $remaining);
    }

    public function test_uses_default_alert_days_when_null(): void
    {
        // 30 days from 2026-06-15 = 2026-07-15
        $this->assertSame(
            DocumentExpiryStatus::ExpiringSoon,
            $this->calculator->resolveStatus(true, '2026-07-15', null, $this->today)
        );

        // 31 days from 2026-06-15 = 2026-07-16
        $this->assertSame(
            DocumentExpiryStatus::Valid,
            $this->calculator->resolveStatus(true, '2026-07-16', null, $this->today)
        );
    }
}
