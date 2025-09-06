<?php
declare(strict_types=1);
namespace App\Attendance\Config;
use App\Models\Setting;
final class AttendanceConfig {
    private array $map = [
        'allowed_before_hours' => 'attendance.allowed_before_hours',
        'allowed_after_hours' => 'attendance.allowed_after_hours',
        'pre_end_hours' => 'attendance.pre_end_hours_for_check_in_out',
        'early_arrival_minutes' => 'attendance.early_arrival_minutes',
        'late_departure_minutes' => 'attendance.late_departure_minutes',
    ];
    private ?int $allowedBeforeHours = null;
    private ?int $allowedAfterHours = null;
    private ?int $preEndHours = null;
    private ?int $earlyArrivalMinutes = null;
    private ?int $lateDepartureMinutes = null;
    public function allowedBeforeHours(): int { return $this->allowedBeforeHours ??= (int) Setting::getSetting($this->map['allowed_before_hours'], 0); }
    public function allowedAfterHours(): int { return $this->allowedAfterHours ??= (int) Setting::getSetting($this->map['allowed_after_hours'], 0); }
    public function preEndHours(): int { return $this->preEndHours ??= (int) Setting::getSetting($this->map['pre_end_hours'], 1); }
    public function earlyArrivalMinutes(): int { return $this->earlyArrivalMinutes ??= (int) Setting::getSetting($this->map['early_arrival_minutes'], 0); }
    public function lateDepartureMinutes(): int { return $this->lateDepartureMinutes ??= (int) Setting::getSetting($this->map['late_departure_minutes'], 0); }
}
