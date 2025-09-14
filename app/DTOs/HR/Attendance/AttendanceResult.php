<?php 
namespace App\DTOs\HR\Attendance;

final class AttendanceResult {
    public bool $ok;
    public ?string $message;
    public ?array $data; // DTO مفصلة
}
