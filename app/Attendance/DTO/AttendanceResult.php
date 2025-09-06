<?php
declare(strict_types=1);
namespace App\Attendance\DTO;
final class AttendanceResult {
    public function __construct(
        public bool $ok,
        public ?string $message = null,
        public ?AttendanceDTO $data = null
    ) {}
    public static function ok(?AttendanceDTO $data = null, ?string $message = null): self { return new self(true, $message, $data); }
    public static function fail(string $message): self { return new self(false, $message, null); }
}
