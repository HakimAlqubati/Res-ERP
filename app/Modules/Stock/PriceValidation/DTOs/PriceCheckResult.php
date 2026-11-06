<?php

namespace App\Modules\Stock\PriceValidation\DTOs;

/**
 * Immutable result of a price-change validation check.
 *
 * This DTO is framework-agnostic — it carries raw data only.
 * The caller decides how to present it (notification, log, exception, etc.).
 */
final class PriceCheckResult
{
    public function __construct(
        public readonly int    $productId,
        public readonly int    $unitId,
        public readonly bool   $exceeds,
        public readonly float  $changePercent,
        public readonly float  $normalizedLastPrice,
        public readonly float  $normalizedNewPrice,
        public readonly float  $maxAllowedPercent,
        public readonly ?LastPriceRecord $lastPriceRecord = null,
    ) {}

    /**
     * Does this result require user attention?
     */
    public function requiresWarning(): bool
    {
        return $this->exceeds;
    }

    /**
     * Human-readable direction of the price movement.
     */
    public function direction(): string
    {
        if ($this->changePercent > 0) {
            return 'increase';
        }

        if ($this->changePercent < 0) {
            return 'decrease';
        }

        return 'unchanged';
    }

    /**
     * Plain-text summary suitable for any output channel.
     */
    public function toSummary(): string
    {
        $absPercent  = abs(round($this->changePercent, 1));
        $lastDisplay = number_format($this->normalizedLastPrice, 2);
        $newDisplay  = number_format($this->normalizedNewPrice, 2);

        return implode(' | ', [
            "Change: {$absPercent}% ({$this->direction()})",
            "Max allowed: {$this->maxAllowedPercent}%",
            "Last: {$lastDisplay}",
            "New: {$newDisplay}",
        ]);
    }

    /**
     * Structured array representation for serialization or logging.
     */
    public function toArray(): array
    {
        return [
            'product_id'            => $this->productId,
            'unit_id'               => $this->unitId,
            'exceeds'               => $this->exceeds,
            'change_percent'        => $this->changePercent,
            'direction'             => $this->direction(),
            'normalized_last_price' => $this->normalizedLastPrice,
            'normalized_new_price'  => $this->normalizedNewPrice,
            'max_allowed_percent'   => $this->maxAllowedPercent,
            'source_type'           => $this->lastPriceRecord?->sourceType,
            'source_id'             => $this->lastPriceRecord?->sourceId,
            'source_date'           => $this->lastPriceRecord?->sourceDate,
        ];
    }

    /**
     * Factory: create a "no warning" result when validation passes or is skipped.
     */
    public static function ok(int $productId, int $unitId): self
    {
        return new self(
            productId:           $productId,
            unitId:              $unitId,
            exceeds:             false,
            changePercent:       0.0,
            normalizedLastPrice: 0.0,
            normalizedNewPrice:  0.0,
            maxAllowedPercent:   0.0,
        );
    }
}
