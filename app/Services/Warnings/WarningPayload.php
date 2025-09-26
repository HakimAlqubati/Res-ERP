<?php
// app/Services/Warnings/WarningPayload.php
namespace App\Services\Warnings;

use App\Enums\Warnings\WarningLevel;
use Ramsey\Uuid\Uuid;

final class WarningPayload
{
    public function __construct(
        public string $title,
        public string|array $detail,
        public WarningLevel $level = WarningLevel::Warning,
        public ?array $context = null,
        public ?string $link = null,
        public ?string $scopeKey = null,
        public ?string $expiresAt = null,
        public string $status = 'active'
    ) {}

    public static function make(string $title, string|array $detail, WarningLevel  $level = WarningLevel::Warning): self
    {
        return new self($title, $detail, $level);
    }

    public function ctx(array $context): self
    {
        $this->context = $context;
        return $this;
    }
    public function url(?string $link): self
    {
        $this->link = $link;
        return $this;
    }
    public function scope(string $key): self
    {
        $this->scopeKey = $key;
        return $this;
    }
    public function expires(\DateTimeInterface $at): self
    {
        $this->expiresAt = $at->format(DATE_ATOM);
        return $this;
    }

    public function deterministicId(): string
    {
        $base = ($this->scopeKey ?: $this->title) . '|' . $this->level->value;
        return 'w:' . md5($base);
    }


    public function toDatabaseArray(): array
    {
        return [
            'title'      => $this->title,
            'detail'     => $this->detail,
            'level'      => $this->level->value,
            'context'    => $this->context,
            'link'       => $this->link,
            'scope_key'  => $this->scopeKey,
            'expires_at' => $this->expiresAt,
            'status'     => $this->status,
        ];
    }
}
