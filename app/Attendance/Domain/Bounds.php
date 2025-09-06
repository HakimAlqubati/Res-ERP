<?php
declare(strict_types=1);
namespace App\Attendance\Domain;
use Carbon\CarbonImmutable;
final class Bounds {
    public function __construct(
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public CarbonImmutable $windowStart,
        public CarbonImmutable $windowEnd,
        public bool $isOvernight
    ) {}
}
