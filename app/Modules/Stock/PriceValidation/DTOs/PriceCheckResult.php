<?php

namespace App\Modules\Stock\PriceValidation\DTOs;

/**
 * Immutable result of a price-change validation check.
 *
 * Contains everything the UI needs to display a meaningful
 * warning notification to the user.
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
     * Quick check: does this result require user attention?
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
     * Build a notification body suitable for Filament Notification.
     */
    public function toNotificationBody(): string
    {
        $direction   = $this->changePercent > 0 ? '📈' : '📉';
        $absPercent  = abs(round($this->changePercent, 1));
        $lastDisplay = number_format($this->normalizedLastPrice, 2);
        $newDisplay  = number_format($this->normalizedNewPrice, 2);

        return implode("\n", [
            "{$direction} Price changed by {$absPercent}% (max allowed: {$this->maxAllowedPercent}%)",
            "Last price (per unit): {$lastDisplay}",
            "New price (per unit): {$newDisplay}",
        ]);
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
