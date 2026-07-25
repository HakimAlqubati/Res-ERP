<?php

namespace App\Modules\Stock\PriceValidation\Services;

use App\Modules\Stock\PriceValidation\Contracts\LastPurchasePriceRepositoryInterface;
use App\Modules\Stock\PriceValidation\Contracts\PriceChangeValidatorInterface;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckItem;
use App\Modules\Stock\PriceValidation\DTOs\PriceCheckResult;

/**
 * Core service that validates price changes against historical data.
 *
 * Responsibilities:
 *  - Read the configured max-allowed percentage from settings.
 *  - Fetch the last purchase price via the injected repository.
 *  - Normalise prices using package_size for cross-unit comparison.
 *  - Return a PriceCheckResult with all details for the UI layer.
 *
 * This service does NOT trigger UI notifications. That is the
 * caller's responsibility (see PriceChangeNotifier for a helper).
 */
class PriceChangeValidator implements PriceChangeValidatorInterface
{
    private const SETTING_KEY = 'max_price_change_percent';

    public function __construct(
        private readonly LastPurchasePriceRepositoryInterface $priceRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function validate(PriceCheckItem $item): PriceCheckResult
    {
        // Guard: feature disabled or invalid input.
        if (!$this->isEnabled() || !$item->isValid()) {
            return PriceCheckResult::ok($item->productId, $item->unitId);
        }

        $maxPercent = $this->getMaxAllowedPercent();

        // Fetch last price (same unit preferred, any unit as fallback).
        $lastRecord = $this->priceRepository->getLastPrice(
            $item->productId,
            $item->unitId,
        );

        // No history → nothing to compare against.
        if ($lastRecord === null) {
            return PriceCheckResult::ok($item->productId, $item->unitId);
        }

        // Convert prices to the current purchase unit's package size for comparison and display.
        $normalizedLast = $lastRecord->normalizedPrice() * $item->packageSize;
        $normalizedNew  = $item->newPrice;

        // Prevent division by zero.
        if ($normalizedLast == 0) {
            return PriceCheckResult::ok($item->productId, $item->unitId);
        }

        // Calculate signed percentage change.
        $changePercent = (($normalizedNew - $normalizedLast) / $normalizedLast) * 100;

        // Compare absolute value against threshold.
        $exceeds = abs($changePercent) > $maxPercent;

        return new PriceCheckResult(
            productId:           $item->productId,
            unitId:              $item->unitId,
            exceeds:             $exceeds,
            changePercent:       round($changePercent, 2),
            normalizedLastPrice: round($normalizedLast, 4),
            normalizedNewPrice:  round($normalizedNew, 4),
            maxAllowedPercent:   $maxPercent,
            lastPriceRecord:     $lastRecord,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function validateMany(array $items): array
    {
        if (!$this->isEnabled()) {
            return array_map(
                fn(PriceCheckItem $item) => PriceCheckResult::ok($item->productId, $item->unitId),
                $items,
            );
        }

        $results = [];

        foreach ($items as $key => $item) {
            $results[$key] = $this->validate($item);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->getMaxAllowedPercent() > 0;
    }

    /**
     * Read the max-allowed percentage from the settings table.
     */
    private function getMaxAllowedPercent(): float
    {
        return (float) settingWithDefault(self::SETTING_KEY, 0);
    }
}
